<?php
namespace App\Http\Traits;

use App\Utils\MyRedis;
use App\Facades\LvRedisFacade as Redis;

trait ClearCacheTrait
{
    /**
     * 清除后台登录信息
     * @param int $userId
     * @return bool
     */
    public function clearXtoken($userId = 0) {
        if ( $userId <= 0 ) {
            ///
            return false;
        }

        $redisKey = config('redisKey');
        $rbacKey = sprintf($redisKey['rbac']['key'], $userId);
        $xTokenKey = sprintf($redisKey['x_token']['key'], $userId);
        $userInfoKey = sprintf($redisKey['user_info']['key'], $userId);

        // $result1 = MyRedis::del($rbacKey);
        // $result2 = MyRedis::del($xTokenKey);
        // $result3 = MyRedis::del($userInfoKey);
        
        $result1 = Redis::del($rbacKey);
        $result2 = Redis::del($xTokenKey);
        $result3 = Redis::del($userInfoKey);


        ///
        return $result1 && $result2 && $result3;
    }

    /**
     * 清除会员登录信息
     * @param int $mId
     * @param int $mSubUid
     * @return bool
     */
    public function clearMtoken($mId = 0,$ext = '*') {
        if ( $mId <= 0 ) {
            return false;
        }
        $redisKey = config('redisKey');
        $keys = sprintf($redisKey['m_token']['key'], $mId,$ext);
        $KeyMulti = Redis::keys($keys);
        foreach ($KeyMulti as $v){
            $result1 = Redis::del($v);
        }
        if ($ext == '*'){//清空所有多登以及自己
            $userInfoKey = sprintf($redisKey['mem_info']['key'], $mId);
            $result2 = Redis::del($userInfoKey);
        }
        return true;
    }

    /**
     * 会员一键踢出其他人
     * @param int $mId
     * @param $auth
     * @return bool
     */
    public function clearOthersMtoken($mId = 0, $auth) {
        if ( $mId <= 0 ) {
            return false;
        }

        $redisKey = config('redisKey');
        $keys = sprintf($redisKey['m_token']['key'], $mId, '*');
        $KeyMulti = Redis::keys($keys);

        foreach ($KeyMulti as $v){
            if (!strpos($v, $auth) !== false) {
                $result1 = Redis::del($v); // 删除其他人
            }
        }
        return true;
    }
}
