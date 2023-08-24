<?php

namespace App\Model\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class User extends Model
{
    /**
     * 获取用户信息
     * @param $id
     * @return array
     */
    public function getCurUser($id)
    {
        $redisKey = config('redisKey');
        $rbacKey = sprintf($redisKey['rbac']['key'], $id); // 角色权限信息
        $userInfoKey = sprintf($redisKey['user_info']['key'], $id); // 用户信息

        // from redis
        $user = Redis::hgetall($userInfoKey);
        $rbac = Redis::hgetall($rbacKey);
        if (!empty($user) && !empty($rbac) ) {
            $roles = json_decode($rbac['role'], true);
            $permission = json_decode($rbac['permission'], true);
            $nav = json_decode($rbac['nav'], true);

            return [
                'user' => $user,
                'roles' => $roles,
                'permissions' => $permission,
                'nav' => $nav
            ];
        }

        // from mysql
        $user = $this->where(['id' => $id])->first()->toArray();
        $roleIds = json_decode($user['roles'], true);
        if (!empty($roleIds)) {
            $rolesList = DB::table('roles')->whereIn('id', $roleIds)->get();
        }

        $roles = [];
        $permissionsTmp = [];
        if (!empty($rolesList)) {
            foreach ($rolesList as $role) {
                $roles[] = $role->name;
                $permission = json_decode($role->permission, true);
                $permissionsTmp = array_keys(@array_flip($permissionsTmp)+@array_flip($permission));
            }
        }

        $permissions = [];
        if ($permissionsTmp) {
            foreach ($permissionsTmp as $permission) {
                $permissionArr = explode('_', $permission);
                $count = count($permissionArr);
                $permissions[$permission] = $permission;

                if ($count > 1)  {
                    for ($i = 0; $i < $count; $i++) {
                        if ($i == 0) {
                            $permissions[$permissionArr[$i]] = $permissionArr[$i];
                        } else {
                            $index = $permissionArr[$i-1] . '_' . $permissionArr[$i];
                            $permissions[$index] = $index;
                        }
                    }
                }
            }
        }
        sort($permissions);

        // 栏目id
        $nav = [];
        $navTmp = DB::table('permissions')->whereIn('id', $permissions)->get('p_id')->toArray();
        if (!empty($navTmp)) {
            foreach ($navTmp as $val) {
                $nav[] = $val->p_id;
            }
        }
        $nav = array_merge($nav, $permissions);
        $nav = array_unique($nav);
        sort($nav);

        // 栏目path
        $pathTmp = DB::table('permissions')
            ->whereIn('id', $nav)
            ->where(function ($q) {
                $q->where('is_show', 1)
                    ->orWhere('p_id', 0);
            })
            ->get('path')->toArray();
        $pathList = [];
        if (! empty($pathTmp) ) {
            foreach ($pathTmp as $val) {
                $pathList[] = $val->path;
            }
        }

        // set redis
        Redis::hmset($rbacKey, [
            'role' => json_encode($roles),
            'permission' => json_encode($permissions),
            'nav' => json_encode($pathList),
        ]);

        return [
            'user' => $user,
            'roles' => $roles,
            'permissions' => $permissions,
            'nav' => $pathList,
        ];
    }
}
