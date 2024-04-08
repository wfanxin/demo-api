<?php

namespace App\Http\Controllers\Api;

use App\Http\Traits\FormatTrait;
use App\Model\Api\Member;
use App\Model\Api\Payment;
use App\Model\Api\PayRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

/**
 * 用户
 */
class MemberController extends Controller
{
    use FormatTrait;

    /**
     * 用户登录
     * @param Request $request
     */
    public function login(Request $request, Member $mMember)
    {
        $params = $request->all();

        $username = $params['username'] ?? '';
        $password = $params['password'] ?? '';

        if (empty($username)) {
            return $this->jsonAdminResult([],10001,'账号不能为空');
        }

        if (empty($password)) {
            return $this->jsonAdminResult([],10001,'密码不能为空');
        }

        $info = $mMember->where('user_name', $username)->first();
        $info = $this->dbResult($info);
        if (empty($info)) {
            return $this->jsonAdminResult([],10001,'账号或密码错误');
        }

        if ($this->_encodePwd($password, $info['salt']) != $info['password']) {
            return $this->jsonAdminResult([],10001,'账号或密码错误');
        }

        $redisKey = config('redisKey');
        $mTokenKey = sprintf($redisKey['m_token']['key'], $info['id']); // 登录授权令牌信息
        $memInfoKey = sprintf($redisKey['mem_info']['key'], $info['id']); // 用户信息

        // 发放校验令牌
        $time = time();
        $auth = md5(md5(sprintf("%s_%s_%s", $time, '34jkjf234KGDF3ORGI4j', $info['id'])));
        $token = sprintf("%s|%s|%s", $auth, $time, $info['id']);

        Redis::set($mTokenKey, $token);
        Redis::expire($mTokenKey, $redisKey['m_token']['ttl']);
        Redis::hmset($memInfoKey, $info);

        return $this->jsonAdminResult(['token' => $token]);
    }

    /**
     * 退出登录
     * @param Request $request
     */
    public function logout(Request $request, Member $mMember)
    {
        $params = $request->all();

        $redisKey = config('redisKey');
        $mTokenKey = sprintf($redisKey['m_token']['key'], $request->memId); // 登录授权令牌信息
        $memInfoKey = sprintf($redisKey['mem_info']['key'], $request->memId); // 用户信息

        Redis::del($mTokenKey);
        Redis::del($memInfoKey);

        return $this->jsonAdminResult();
    }

    /**
     * 用户信息
     * @param Request $request
     */
    public function getMember(Request $request, Member $mMember) {
        $params = $request->all();

        $info = $mMember->where('id', $request->memId)->first();
        $info = $this->dbResult($info);

        if (!empty($info)) {
            $urlPre = config('filesystems.disks.tmp.url');
            if (!empty($info['avatar'])) {
                $info['avatar'] = $urlPre . $info['avatar'];
            }
        }

        return $this->jsonAdminResult(['data' => $info]);
    }

    /**
     * 编辑用户
     * @param Request $request
     */
    public function editMember(Request $request, Member $mMember) {
        $params = $request->all();

        $method = $params['method'] ?? '';

        $res = true;
        if ($method == 'avatar') {
            $avatar = $params['avatar'] ?? '';
            $urlPre = config('filesystems.disks.tmp.url');
            $avatar = str_replace($urlPre, '', $avatar);
            $avatar = str_replace('/static/avatar.png', '', $avatar);
            if (empty($avatar)) {
                return $this->jsonAdminResult([],10001,'头像不能为空');
            }

            $res = $mMember->where('id', $request->memId)->update(['avatar' => $avatar]);
        } else if ($method == 'name') {
            $name = $params['name'] ?? '';
            if (empty($name)) {
                return $this->jsonAdminResult([],10001,'姓名不能为空');
            }

            $res = $mMember->where('id', $request->memId)->update(['name' => $name]);
        } else if ($method == 'password') {
            $oldPassword = $params['oldPassword'] ?? '';
            $password = $params['password'] ?? '';
            $cfpassword = $params['cfpassword'] ?? '';

            if (empty($oldPassword)) {
                return $this->jsonAdminResult([],10001,'原始密码不能为空');
            }

            if (empty($password)) {
                return $this->jsonAdminResult([],10001,'新密码不能为空');
            }

            if (empty($cfpassword)) {
                return $this->jsonAdminResult([],10001,'确认新密码不能为空');
            }

            if ($password != $cfpassword) {
                return $this->jsonAdminResult([],10001,'新密码不一致');
            }

            $info = $mMember->where('id', $request->memId)->first();
            $info = $this->dbResult($info);

            if ($this->_encodePwd($oldPassword, $info['salt']) != $info['password']) {
                return $this->jsonAdminResult([],10001,'原始密码错误');
            }

            $res = $mMember->where('id', $request->memId)->update(['password' => $this->_encodePwd($password, $info['salt'])]);
        }

        if ($res) {
            return $this->jsonAdminResult();
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }
}
