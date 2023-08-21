<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Admin\Controller;
use App\Http\Requests\Admin\Login;
use App\Http\Traits\ClearCacheTrait;
use App\Model\Admin\User;
use App\Utils\MyRedis;
use Illuminate\Http\Request;
use App\Facades\LvRedisFacade as Redis;

/**
 * 用户授权令牌
 * @name 用户授权令牌
 * Class TokenController
 * @package App\Http\Controllers
 *
 * @PermissionWhiteList
 * @Resource("tokens")
 */
class TokenController extends Controller
{
    use ClearCacheTrait;

    /**
     * 登录（获取令牌，将令牌保存至客户端。在需要校验身份的请求的头信息里，添加：x-Token="授权的令牌"）
     * @name 登录（获取令牌，将令牌保存至客户端。在需要校验身份的请求的头信息里，添加：x-Token="授权的令牌"）
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * @Post("/lv/tokens")
     * @Versions("v1")
     *
     @Request("user_name={user_name}&password={password}&page={page}", contentType="application/x-www-form-urlencoded", attributes={
        @Attribute("user_name", type="string", required=true, description="用户名", sample="zhangsan"),
        @Attribute("password", type="string", required=true, description="密码", sample="12312233"),
     })
     @Response(200, body={
        "code":0,
        "message":"success",
        "data":{"token": "06b00f3c7db7b9fbc2c83f90cd6304d4|1563844974|1"}
    },
    attributes={
        @Attribute("token", type="string", description="授权的令牌", sample=10,required=true),
    })
     */
    public function store(Login $request, User $user)
    {
        //
        $redisKey = config('redisKey');

        $where = [
            'user_name' => $request['user_name'],
        ];
        $userInfo = $user->where($where)->first();
        if (empty($userInfo)) {
            return $this->jsonAdminResultWithLog($request,[], 10003);
        }

        if ($userInfo['status'] == 2) {
            return $this->jsonAdminResultWithLog($request,[], 10005);
        }

        if ($userInfo['error_amount'] >= 5) { //超过五次
            $user->where(['id' => $userInfo['id']])->update([
                'status' => 2
            ]);

            return $this->jsonAdminResultWithLog($request,[], 10005);
        }

        $request->userId = $userInfo['id'];

        ///验证密码
        if ( empty($userInfo) || $userInfo['password'] != $this->_encodePwd($request['password'], $userInfo['salt'])) {
            $user->where(['user_name' => $request['user_name']])->increment('error_amount');
            return $this->jsonAdminResultWithLog($request,[], 10003);
        }
        // 清除旧的登录信息缓存
        $this->clearXtoken($request->userId);

        $user->where(['user_name' => $request['user_name']])->update([
            'error_amount' => 0,
            'last_ip' =>$request->getClientIp(),
            'updated_at' => date("Y-m-d H:i:s")
        ]);

        ///
        $xTokenKey = sprintf($redisKey['x_token']['key'], $userInfo['id']);
        $userInfoKey = sprintf($redisKey['user_info']['key'], $userInfo['id']);

        //发放校验令牌
        $time = time();
        $auth = md5(md5(sprintf("%s_%s_%s", $time, "34jkjf234KGDF3ORGI4j", $userInfo['id'])));
        $token = sprintf("%s|%s|%s",$auth, $time, $userInfo['id']);
        // MyRedis::set($xTokenKey, $token);
        // MyRedis::expire($xTokenKey, 86400);
        // MyRedis::hmset($userInfoKey, $userInfo->toArray());

        Redis::set($xTokenKey, $token);
        Redis::expire($xTokenKey, 86400);
        Redis::hmset($userInfoKey, $userInfo->toArray());

        unset($request->userId); //没这个参数不会记录操作log
        return $this->jsonAdminResultWithLog($request,[
            'token' => $token
        ]);
    }

    /**
     * 退出登录（销毁授权令牌）
     * @name 退出登录（销毁授权令牌）
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     *
     * @Delete("/lv/tokens/{?id}")
     * @Versions("v1")
     *
     * @Response(200, body={
        "code":0,
        "message":"success",
        "data":"[]"
        })
     */
    public function destroy(Request $request, $id)
    {
        if ($request->userId != $id) {
            return $this->jsonAdminResultWithLog($request,[], 10002);
        }

        //
        $result = $this->clearXtoken($request->userId);
        if ($result) {
            unset($request->userId); //没这个参数不会记录操作log
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResultWithLog($request,[], 10001);
        }
    }
}
