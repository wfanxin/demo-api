<?php

namespace App\Http\Controllers\Admin\Mobile;

use App\Http\Controllers\Admin\Controller;
use App\Http\Traits\FormatTrait;
use App\Model\Admin\Config;
use App\Model\Admin\Product;
use Illuminate\Http\Request;

/**
 * @name 选项管理
 * Class OptionController
 * @package App\Http\Controllers\Admin\Mobile
 *
 */
class OptionController extends Controller
{
    use FormatTrait;

    /**
     * @name 选项配置
     * @Get("/lv/base/option/detail")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function detail(Request $request, Config $mConfig)
    {
        $params = $request->all();
        $params['userId'] = $request->userId;

        $where = [];
        $where[] = ['name', '=', 'option'];
        $content = $mConfig->where($where)->value('content');
        if (empty($content)) {
            $content = [];
            $content['export_port'] = ['name' => '出口港', 'value' => ''];
            $content['destination_port'] = ['name' => '目的港', 'value' => ''];
        } else {
            $content = json_decode($content, true);
        }

        return $this->jsonAdminResult([
            'data' => $content
        ]);
    }

    /**
     * @name 编辑选项
     * @Post("/lv/base/option/edit")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function edit(Request $request, Config $mConfig)
    {
        $params = $request->all();

        $res = true;
        if ($mConfig->where('name', 'option')->count() > 0) {
            $res = $mConfig->where('name', 'option')->update(['content' => json_encode($params, JSON_UNESCAPED_UNICODE)]);
        } else {
            $res = $mConfig->where('name', 'option')->insert([
                'name' => 'option',
                'content' => json_encode($params, JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }

        if ($res) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResultWithLog($request, [],10001);
        }
    }
}
