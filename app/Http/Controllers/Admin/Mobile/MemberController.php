<?php

namespace App\Http\Controllers\Admin\Mobile;

use App\Http\Controllers\Admin\Controller;
use App\Http\Traits\FormatTrait;
use App\Model\Api\Member;
use App\Model\Api\Payment;
use Illuminate\Http\Request;

/**
 * @name 会员管理
 * Class MemberController
 * @package App\Http\Controllers\Admin\Mobile
 *
 * @Resource("slides")
 */
class MemberController extends Controller
{
    use FormatTrait;

    /**
     * @name 会员列表
     * @Get("/lv/mobile/member/list")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function list(Request $request, Member $mMember)
    {
        $params = $request->all();
        $params['userId'] = $request->userId;

        $where = [];

        // 手机号
        if (!empty($params['mobile'])){
            $where[] = ['mobile', 'like', '%' . $params['mobile'] . '%'];
        }

        // 姓名
        if (!empty($params['name'])){
            $where[] = ['name', 'like', '%' . $params['name'] . '%'];
        }

        $orderField = 'id';
        $sort = 'desc';
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? config('global.page_size');
        $data = $mMember->where($where)
            ->orderBy($orderField, $sort)
            ->paginate($pageSize, ['*'], 'page', $page);

        if (!empty($data->items())) {
            $urlPre = config('filesystems.disks.tmp.url');
            foreach ($data->items() as $k => $v) {
                if (!empty($v->avatar)) {
                    $data->items()[$k]['avatar'] = $urlPre . $v->avatar;
                }
            }
        }

        return $this->jsonAdminResult([
            'total' => $data->total(),
            'data' => $data->items()
        ]);
    }

    /**
     * @name 修改会员
     * @Post("/lv/mobile/member/edit")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function edit(Request $request, Member $mMember)
    {
        $params = $request->all();

        $id = $params['id'] ?? 0;
        $mobile = $params['mobile'] ?? '';
        $name = $params['name'] ?? '';
        $password = $params['password'] ?? '';
        $cfpassword = $params['cfpassword'] ?? '';

        if (empty($id)) {
            return $this->jsonAdminResult([],10001, '参数错误');
        }

        $info = $mMember->where('id', $id)->first();
        $info = $this->dbResult($info);
        if (empty($info)) {
            return $this->jsonAdminResult([],10001, '用户信息不存在');
        }

        if (empty($mobile)) {
            return $this->jsonAdminResult([],10001, '手机号不能为空');
        }

        $pattern = '/^1[0-9]{10}$/';
        if (!preg_match($pattern, $mobile)) {
            return $this->jsonAdminResult([],10001,'手机号格式不正确');
        }

        $count = $mMember->where('id', '!=' , $id)->where('mobile', $mobile)->count();
        if ($count > 0) {
            return $this->jsonAdminResult([],10001, '手机号已存在');
        }

        if (empty($name)) {
            return $this->jsonAdminResult([],10001, '姓名不能为空');
        }

        $data = [];
        $data['mobile'] = $mobile;
        $data['name'] = $name;

        if (!empty($password)) {
            if (empty($cfpassword)) {
                return $this->jsonAdminResult([],10001, '确认新密码不能为空');
            }
            if ($password != $cfpassword) {
                return $this->jsonAdminResult([],10001, '确认新密码不一致');
            }

            $salt = $info['salt'];
            $data['password'] = $this->_encodePwd($password, $salt);
        }

        $res = $mMember->where('id', $id)->update($data);

        if ($res) {
            return $this->jsonAdminResult();
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }

    /**
     * @name 删除会员
     * @Post("/lv/mobile/member/del")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function del(Request $request, Member $mMember)
    {
        $params = $request->all();

        $id = $params['id'] ?? 0;

        if (empty($id)) {
            return $this->jsonAdminResult([],10001,'参数错误');
        }

        $info = $mMember->where('id', $id)->first();
        $info = $this->dbResult($info);
        if (empty($info)) {
            return $this->jsonAdminResult([],10001,'会员不存在');
        }

        $res = $mMember->delMember($info);

        if ($res) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }
}
