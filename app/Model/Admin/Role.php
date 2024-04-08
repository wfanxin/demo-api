<?php

namespace App\Model\Admin;

use App\Http\Traits\FormatTrait;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use FormatTrait;

    /**
     * 获取角色选项
     * @param array $params
     * @return array
     */
    public function getRoleOptions($params = []){
        $mUser = new User();
        $roles = $mUser->where('id', $params['userId'])->value('roles');
        if (empty($roles)) {
            return [];
        }
        $roles = json_decode($roles, true);
        if (empty($roles)) {
            return [];
        }

        $not_id = $params['id'] ?? 0;

        $role_info = $this->where('id', $roles[0])->where('id', '!=', $not_id)->first();
        $role_info = $this->dbResult($role_info);
        if (empty($role_info)) {
            return [];
        }

        $options = [];
        $children = $this->getChildren($role_info['id'], $not_id);
        if (empty($children)) {
            $options[] = [
                'value' => $role_info['id'],
                'label' => $role_info['name'] == 'admin' ? '超级管理员' : $role_info['name']
            ];
        } else {
            $options[] = [
                'value' => $role_info['id'],
                'label' => $role_info['name'] == 'admin' ? '超级管理员' : $role_info['name'],
                'children' => $children
            ];
        }

        return $options;
    }

    /**
     * 获取角色选择条件
     * @param array $params
     * @return array
     */
    public function getRoleWhere($params = []){
        $ids = [];
        $where = [];

        if ($this->where('p_id', '>', 0)->count() == 0) { // 没有上下级之分
            return $where;
        }

        // 获取当前用户的角色
        $mUser = new User();
        $roles = $mUser->where('id', $params['userId'])->value('roles');
        if (empty($roles)) {
            $where[] = [function ($query) use ($ids) {
                $query->whereIn('id', $ids);
            }];
            return $where;
        }
        $roles = json_decode($roles, true);
        if (empty($roles)) {
            $where[] = [function ($query) use ($ids) {
                $query->whereIn('id', $ids);
            }];
            return $where;
        }

        if ($roles[0] == 1) { // 角色1，为超级管理员
            return $where;
        }

        $ids = [];
        // $ids[] = $roles[0]; // 不用包含自己

        // 获取所有下级角色id
        $p_ids = [$roles[0]];
        while (true) {
            $role_list = $this->whereIn('p_id', $p_ids)->get();
            $role_list = $this->dbResult($role_list);

            if (empty($role_list)) {
                break;
            }

            $p_ids = array_column($role_list, 'id');
            $ids = array_merge($ids, $p_ids);
        }

        $where[] = [function ($query) use ($ids) {
            $query->whereIn('id', $ids);
        }];
        return $where;
    }

    /**
     * 获取角色用户选择条件
     * @param array $params
     * @return array
     */
    public function getRoleUserWhere($params = []){
        $where = $this->getRoleWhere($params);
        if (empty($where)) {
            return $where;
        }

        // 获取所有角色id
        $role_list = $this->where($where)->get();
        $role_list = $this->dbResult($role_list);

        $roles = [];
        foreach ($role_list as  $value) {
            $roles[] = '["' . $value['id'] . '"]';
        }

        $userWhere = [];
        $userWhere[] = [function ($query) use ($roles) {
            $query->whereIn('roles', $roles);
        }];
        return $userWhere;
    }

    /**
     * 获取孩子角色选项
     * @param $p_id
     * @return array
     */
    public function getChildren($p_id, $not_id){
        $role_list = $this->where('p_id', $p_id)->where('id', '!=', $not_id)->get();
        $role_list = $this->dbResult($role_list);

        $options = [];
        foreach ($role_list as $value) {
            $children = $this->getChildren($value['id'], $not_id);
            if (empty($children)) {
                $options[] = [
                    'value' => $value['id'],
                    'label' => $value['name']
                ];
            } else {
                $options[] = [
                    'value' => $value['id'],
                    'label' => $value['name'],
                    'children' => $children
                ];
            }
        }

        return $options;
    }
}
