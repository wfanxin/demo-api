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
     * 用户注册
     * @param Request $request
     */
    public function register(Request $request, Member $mMember, Redis $redis)
    {
        $params = $request->all();

        $mobile = $params['mobile'] ?? '';
        $name = $params['name'] ?? '';
        $mobile_code = $params['mobile_code'] ?? '';
        $password = $params['password'] ?? '';
        $cfpassword = $params['cfpassword'] ?? '';

        if (empty($mobile)) {
            return $this->jsonAdminResult([],10001,'手机号不能为空');
        }

        $pattern = '/^1[0-9]{10}$/';
        // $pattern = '/^1((3[0-9])|(4[57])|(5[012356789])|(8[02356789]))[0-9]{8}$/';
        if (!preg_match($pattern, $mobile)) {
            return $this->jsonAdminResult([],10001,'手机号格式不正确');
        }

        $count = $mMember->where('mobile', $mobile)->count();
        if ($count > 0) {
            return $this->jsonAdminResult([],10001,'该手机号已注册过');
        }

        if (empty($name)) {
            return $this->jsonAdminResult([],10001,'姓名不能为空');
        }

        if (empty($mobile_code)) {
            return $this->jsonAdminResult([],10001,'验证码不能为空');
        }

        if (empty($password)) {
            return $this->jsonAdminResult([],10001,'密码不能为空');
        }

        if (empty($cfpassword)) {
            return $this->jsonAdminResult([],10001,'确认密码不能为空');
        }

        if ($password != $cfpassword) {
            return $this->jsonAdminResult([],10001,'密码和确认密码不一致');
        }

        $config = config('redisKey');
        $mobileKey = sprintf($config['mem_code']['key'], $mobile);
        $verify_code = $redis::get($mobileKey);
        if ($verify_code != $mobile_code) {
            return $this->jsonAdminResult([],10001,'验证码错误');
        }

        // 数据
        $time = date('Y-m-d H:i:s');
        $salt = rand(1000, 9999);
        $password = $this->_encodePwd($password, $salt);
        $data = [
            'mobile' => $mobile,
            'name' => $name,
            'password' => $password,
            'salt' => $salt,
            'created_at' => $time,
            'updated_at' => $time
        ];

        $res = $mMember->insert($data);
        if ($res) {
            return $this->jsonAdminResult();
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }

    /**
     * 用户登录
     * @param Request $request
     */
    public function login(Request $request, Member $mMember)
    {
        $params = $request->all();

        $mobile = $params['mobile'] ?? '';
        $password = $params['password'] ?? '';

        if (empty($mobile)) {
            return $this->jsonAdminResult([],10001,'手机号不能为空');
        }

        if (empty($password)) {
            return $this->jsonAdminResult([],10001,'密码不能为空');
        }

        $info = $mMember->where('mobile', $mobile)->first();
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
     * 忘记密码
     * @param Request $request
     */
    public function forget(Request $request, Member $mMember, Redis $redis)
    {
        $params = $request->all();

        $mobile = $params['mobile'] ?? '';
        $mobile_code = $params['mobile_code'] ?? '';
        $password = $params['password'] ?? '';
        $cfpassword = $params['cfpassword'] ?? '';

        if (empty($mobile)) {
            return $this->jsonAdminResult([],10001,'手机号不能为空');
        }

        if (empty($mobile_code)) {
            return $this->jsonAdminResult([],10001,'验证码不能为空');
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

        $config = config('redisKey');
        $mobileKey = sprintf($config['mem_code']['key'], $mobile);
        $verify_code = $redis::get($mobileKey);
        if ($verify_code != $mobile_code) {
            return $this->jsonAdminResult([],10001,'验证码错误');
        }

        $info = $mMember->where('mobile', $mobile)->first();
        $info = $this->dbResult($info);
        if (empty($info)) {
            return $this->jsonAdminResult([],10001,'手机号还未注册');
        }

        $res = $mMember->where('id', $info['id'])->update(['password' => $this->_encodePwd($password, $info['salt'])]);
        if ($res) {
            return $this->jsonAdminResult();
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
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
            $avatar = str_replace('/static/logo.png', '', $avatar);
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
