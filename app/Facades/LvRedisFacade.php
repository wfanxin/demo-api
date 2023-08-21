<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * 系统再次封装predis组件
 * Class LvRedisFacade
 * @package App\Facades
 *
 * @see \App\Utils\LvRedis
 */
class LvRedisFacade extends Facade
{
    public static function getFacadeAccessor()
    {
        return "\App\Utils\LvRedis";
    }
}