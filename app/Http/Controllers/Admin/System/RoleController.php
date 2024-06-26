<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Admin\Controller;
use App\Model\Admin\Permission;
use App\Model\Admin\Role;
use App\Model\Admin\User;
use Illuminate\Http\Request;

/**
 * 后台角色管理
 * @name 角色管理
 * Class RoleController
 * @package App\Http\Controllers\System
 *
 * @Resource("roles")
 */
class RoleController extends Controller
{
    /**
     * 获取全部角色
     * @name 获取全部角色
     * @param Request $request
     * @param Role $role
     * @return \Illuminate\Http\JsonResponse
     * @PermissionWhiteList
     *
     * @Get("/lv/roles/total")
     * @Versions("v1")
     *
    @Response(200, body={
        "code":0,
        "message":"success",
        "roles":{
            {
                "id": 2,
                "id_path": "",
                "key": "Order",
                "name": "订单",
                "children": {
                    {
                        "id": 67,
                        "id_path": "2",
                        "key": "Member_Order_DeliveryController",
                        "name": "批量发货",
                        "children": {
                            {
                                "id": 68,
                                "id_path": "2|67",
                                "key": "@Get:api_delivery_statListPage",
                                "name": "发货记录",
                            }
                        }
                    }
                }
            }
        }
    },
    attributes={
        @Attribute("roles", type="string", description="全部预设角色集合", sample="[]",required=true),
    })
     */
    public function total(Request $request, Role $role)
    {
        $params = $request->all();
        $params['userId'] = $request->userId;

        $where = $role->getRoleWhere($params);

        $data = $role->select(['id', 'name'])->where($where)->get();

        if (!empty($data)) {
            foreach ($data as $k => &$val) {
                if ($val['name'] == 'admin') {
                    $val['name'] = '超级管理员';
                }
            }
        }

        return $this->jsonAdminResultWithLog($request, [
            'roles' => $data
        ]);
    }

    /**
     * 角色列表
     * @name 角色列表
     * @return \Illuminate\Http\Response
     *
     * @Get("/lv/roles")
     * @Versions("v1")
     *
     * @Request("name={name}&page={page}", contentType="application/x-www-form-urlencoded", attributes={
            @Attribute("name", type="string", required=false, description="角色名称", sample="运营"),
            @Attribute("page", type="int", required=false, description="页码", sample="1"),
        })
     *
     * @Response(200, body={
        "code":0,
        "message":"success",
        "data":{
            "total": 10,
            "roles": {
                {
                    "id": 2,
                    "name": "\u3010\u7ba1\u7406\u5458\u3011\u7cfb\u7edf",
                    "permission": "[]",
                    "created_at": "2019-02-25 09:47:19",
                    "updated_at": "2019-07-11 10:20:13"
                }
            }
        }
    },
    attributes={
        @Attribute("total", type="int", description="总条数", sample=10,required=true),
        @Attribute("roles", type="string", description="角色列表集合", sample="[]",required=true),
    })
     */
    public function index(Request $request, Role $role)
    {
        $params = $request->all();
        $params['userId'] = $request->userId;

        $where = $role->getRoleWhere($params);

        $data = $role
            ->where('name', 'like', "%{$params['name']}%")
            ->where('name', '!=', 'admin')
            ->where($where)
            ->paginate(15, ['*'], 'page', $params['page']);

        return $this->jsonAdminResultWithLog($request, [
            'total' => $data->total(),
            'roles' => $data->items()
        ]);
    }

    /**
     * 添加角色
     * @name 添加角色
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * @Post("/lv/roles")
     * @Versions("v1")
     *
     * @Request("name={name}&permission={permission}", contentType="application/x-www-form-urlencoded", attributes={
            @Attribute("name", type="string", required=false, description="角色名称", sample="运营"),
            @Attribute("permission", type="array", required=false, description="权限集合", sample="[]"),
        })
     *
     * @Response(200, body={
            "code": 0,
            "message": "success",
            "data": {}
        })
     */
    public function store(Request $request, Role $role, Permission $permission)
    {
        $params = $request->all();
        $params['userId'] = $request->userId;

        $p_id = 0;
        if (isset($params['p_id'])) {
            if (empty($params['p_id'])) {
                return $this->jsonAdminResultWithLog($request,[], 10001, '请选择上级角色');
            }

            if (is_numeric($params['p_id'])) {
                $p_id = $params['p_id'];
            } else {
                $p_id = $params['p_id'][count($params['p_id']) - 1];
            }

            $where = $permission->getPermissionWhere($params);
            $permission_list = $permission->whereIn('id', $params['rolePermissions'])->where($where)->get();
            $permission_list = json_decode(json_encode($permission_list), true);
            $params['rolePermissions'] = array_column($permission_list, 'id');
        }

        $result = $role->insert([
            'name' => $params['name'],
            'permission' => json_encode($params['rolePermissions']),
            'p_id' => $p_id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        if ($result) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResultWithLog($request, [], 10001);
        }
    }

    /**
     * 编辑角色
     * @name 编辑角色
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     *
     * @Put("/lv/roles/{?id}")
     * @Versions("v1")
     *
     * @Request("name={name}&permission={permission}", contentType="application/x-www-form-urlencoded", attributes={
            @Attribute("name", type="string", required=false, description="角色名称", sample="运营"),
            @Attribute("permission", type="array", required=false, description="权限集合", sample="[]"),
        })
     *
     * @Response(200, body={
            "code": 0,
            "message": "success",
            "data": {}
        })
     */
    public function update(Request $request, $id, Role $role, Permission $permission)
    {
        $params = $request->all();
        $params['userId'] = $request->userId;

        if ($id <= 0) {
            return $this->jsonAdminResultWithLog($request, [], 10002);
        }

        $p_id = 0;
        if (isset($params['p_id'])) {
            if (empty($params['p_id'])) {
                return $this->jsonAdminResultWithLog($request,[], 10001, '请选择上级角色');
            }

            if (is_numeric($params['p_id'])) {
                $p_id = $params['p_id'];
            } else {
                $p_id = $params['p_id'][count($params['p_id']) - 1];
            }

            $where = $permission->getPermissionWhere($params);
            $permission_list = $permission->whereIn('id', $params['rolePermissions'])->where($where)->get();
            $permission_list = json_decode(json_encode($permission_list), true);
            $params['rolePermissions'] = array_column($permission_list, 'id');
        }

        $result = $role->where(['id' => $id])->update([
            'name' => $params['name'],
            'permission' => json_encode($params['rolePermissions']),
            'p_id' => $p_id,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        if ($result) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResultWithLog($request,[], 10001);
        }
    }

    /**
     * 删除角色
     * @name 删除角色
     * @param  int  $id
     * @return \Illuminate\Http\Response
     *
     * @Delete("/lv/roles/{?id}")
     *
     * @Response(200, body={
            "code": 0,
            "message": "success",
            "data": {}
        })
     */
    public function destroy(Request $request, $id, Role $role, User $user)
    {
        if ($id <= 0) {
            return $this->jsonAdminResultWithLog($request, [], 10002);
        }
        $counts = $user->where('roles', '["'.$id.'"]')->count();
        if ($counts){
            return $this->jsonAdminResultWithLog($request, [], 10001,'该角色下已有用户，请先删除用户');
        }
        if ($role->where('p_id', $id)->count() > 0) {
            return $this->jsonAdminResultWithLog($request, [], 10001,'该角色已有下级角色，请先删除下级角色');
        }
        $result = $role->where(['id' => $id])->delete();
        if ($result) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResultWithLog($request, [], 10001);
        }
    }

    /**
     * 批量删除角色
     * @name 批量删除角色
     * @param Request $request
     * @param Role $role
     * @return \Illuminate\Http\JsonResponse
     *
     * @Delete("/lv/roles/batch")
     * @Versions("v1")
     *
     * @Request("ids={ids}", contentType="application/x-www-form-urlencoded", attributes={
            @Attribute("ids", type="string", required=true, description="需要删除的id集合", sample="1,2,3"),
        })
     *
     * @Response(200, body={
            "code": 0,
            "message": "success",
            "data": {},
        })
     */
    public function batchDestroy(Request $request, Role $role, User $user)
    {
        $params = $request->all();

        $ids = explode(',', $params['ids']);
        if (empty($params['ids'])) {
            return $this->jsonAdminResultWithLog($request, [], 10002);
        }
        foreach ($ids as $value) {
            $newIds[] = '["'.$value.'"]';;
        }
        $users = $user->whereIn('roles', $newIds)->orderBy('roles', 'asc')->get(['roles']);
        if (count($users)){
            $role_id = json_decode($users[0]['roles'],true)[0];
            $name = $role->where('id', $role_id)->value('name');
            return $this->jsonAdminResultWithLog($request, [], 10002,'该'.$name.'角色下已有用户，请先删除用户');
        }
        $result = $role->whereIn('id', $ids)->delete();

        if ($result) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResultWithLog($request,[], 10001);
        }
    }

    /**
     * 获取角色选项
     * @name 获取角色选项
     * @Get("/lv/roles/getRoleOptions")
     * @Versions("v1")
     * @PermissionWhiteList
     *
     */
    public function getRoleOptions(Request $request, Role $role)
    {
        $params = $request->all();
        $params['userId'] = $request->userId;

        $options = $role->getRoleOptions($params);

        return $this->jsonAdminResultWithLog($request, [
            'options' => $options
        ]);
    }
}
