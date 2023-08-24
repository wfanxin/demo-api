<?php

namespace App\Http\Controllers\Admin\Property;

use App\Common\Upload;
use App\Common\UploadAdmin;
use App\Exports\PropertyExport;
use App\Http\Controllers\Admin\Controller;
use App\Http\Traits\FormatTrait;
use App\Model\Admin\Property;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @name 物业
 * Class IndexController
 * @package App\Http\Controllers\Admin\Property
 *
 * @Resource("market")
 */
class IndexController extends Controller
{
    use FormatTrait;

    /**
     * @name 物业列表
     * @Get("/lv/property/propertyList")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function propertyList(Request $request, Property $mProperty)
    {
        $params = $request->all();

        $where = [];
        $where[] = ['is_del', '=', 0];
        // 编号
        if (!empty($params['number'])){
            $where[] = ['number', 'like', '%' . $params['number'] . '%'];
        }
        // 物业所属公司
        if (!empty($params['company'])){
            $where[] = ['company', 'like', '%' . $params['company'] . '%'];
        }
        // 物业名称
        if (!empty($params['property_name'])){
            $where[] = ['property_name', 'like', '%' . $params['property_name'] . '%'];
        }
        // 地址
        if (!empty($params['address'])){
            $where[] = ['address', 'like', '%' . $params['address'] . '%'];
        }

        $orderField = 'number';
        $sort = 'asc';
        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? config('global.page_size');
        $data = $mProperty->where($where)
            ->orderByRaw('CONVERT(' . $orderField . ', SIGNED) ' . $sort) // 字符串转整数排序
            ->paginate($pageSize, ['*'], 'page', $page);

        // 域名前缀
        $urlPre = config('filesystems.disks.tmp.url');
        foreach ($data->items() as $k => $v){
            $images = json_decode($v->images, true) ?? [];
            if (!empty($images)) {
                foreach ($images as $key => $value) {
                    $images[$key] = $urlPre . $value;
                }
            }
            $data->items()[$k]['images'] = $images;
        }

        return $this->jsonAdminResult([
            'total' => $data->total(),
            'data' => $data->items()
        ]);
    }

    /**
     * @name 新增物业
     * @Post("/lv/property/addProperty")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function addProperty(Request $request, Property $mProperty)
    {
        $params = $request->all();

        $number = $params['number'] ?? '';
        $company = $params['company'] ?? '';
        $property_type = $params['property_type'] ?? '';
        $property_name = $params['property_name'] ?? '';
        $address = $params['address'] ?? '';
        $area = $params['area'] ?? '';
        $term = $params['term'] ?? '';
        $rent = $params['rent'] ?? '';
        $notes = $params['notes'] ?? '';

        if (empty($number)){
            return $this->jsonAdminResult([],10001, '编号不能为空');
        }

        $info = $mProperty->where('number', $number)->first();
        $info = $this->dbResult($info);
        if (!empty($info)) {
            return $this->jsonAdminResult([],10001, '编号重复');
        }

        $time = date('Y-m-d H:i:s');
        $res = $mProperty->insert([
            'number' => $number,
            'company' => $company,
            'property_type' => $property_type,
            'property_name' => $property_name,
            'address' => $address,
            'area' => $area,
            'term' => $term,
            'rent' => $rent,
            'notes' => $notes,
            'created_at' => $time,
            'updated_at' => $time
        ]);

        if ($res) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }

    /**
     * @name 修改物业
     * @Post("/lv/property/editProperty")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function editProperty(Request $request, Property $mProperty)
    {
        $params = $request->all();

        $id = $params['id'] ?? 0;
        $number = $params['number'] ?? '';
        $company = $params['company'] ?? '';
        $property_type = $params['property_type'] ?? '';
        $property_name = $params['property_name'] ?? '';
        $address = $params['address'] ?? '';
        $area = $params['area'] ?? '';
        $term = $params['term'] ?? '';
        $rent = $params['rent'] ?? '';
        $notes = $params['notes'] ?? '';

        if (empty($id)) {
            return $this->jsonAdminResult([],10001, '参数错误');
        }

        if (empty($number)){
            return $this->jsonAdminResult([],10001, '编号不能为空');
        }

        $info = $mProperty->where('id', '!=', $id)->where('number', $number)->first();
        $info = $this->dbResult($info);
        if (!empty($info)) {
            return $this->jsonAdminResult([],10001, '编号重复');
        }

        $time = date('Y-m-d H:i:s');
        $res = $mProperty->where('id', $id)->update([
            'number' => $number,
            'company' => $company,
            'property_type' => $property_type,
            'property_name' => $property_name,
            'address' => $address,
            'area' => $area,
            'term' => $term,
            'rent' => $rent,
            'notes' => $notes,
            'updated_at' => $time
        ]);

        if ($res !== false) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }

    /**
     * @name 删除
     * @Post("/lv/property/delProperty")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function delProperty(Request $request, Property $mProperty)
    {
        $params = $request->all();

        $id = $params['id'] ?? 0;

        if (empty($id)) {
            return $this->jsonAdminResult([],10001,'参数错误');
        }

        $res = $mProperty->where('id', $id)->delete();

        if ($res !== false) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }

    /**
     * 导入excel
     * @param Request $request
     * @Post("lv/property/uploadFile")
     * @return \Illuminate\Http\JsonResponse
     * @permissionWhiteList
     */
    public function uploadFile(Request $request, Property $mProperty)
    {
        $file = $request->file('file');
        $file_name = $file->getClientOriginalName();
        $file_ext = $file->extension();

        if (! preg_match('/空置物业/', $file_name)) {
            return $this->jsonAdminResult([],10001,'上传文件名错误，必须包含“空置物业”');
        }

        $path = 'admin/' . $request->userId . "/";
        $url = config('filesystems.disks.public.root') . $path;

        if (!is_dir($url)){
            mkdir($url,0777,true);
        }

        // 先把图片上传到临时目录，再移动临时文件到正式目录下
        $upload = new UploadAdmin();
        $file_path = $upload->uploadToPlublic($file, $path, '导入物业列表');

        // 获取excel数据
        $data = [];
        if ($file_ext == 'xls') {
            $data = Excel::toArray([], '/' . $file_path, 'public', \Maatwebsite\Excel\Excel::XLS);
        } else {
            $data = Excel::toArray([], '/' . $file_path, 'public', \Maatwebsite\Excel\Excel::XLSX);
        }

        if (empty($data) || empty($data[0])) {
            return $this->jsonAdminResult([],10001,'表格数据不能为空');
        }

        $import_data = [];
        foreach ($data[0] as $key => $value) {
            if ($key < 2) {
                continue;
            } else if ($key == 2) {
                if (trim($value[0]) != '编号' || trim($value[1]) != '物业所属公司' || trim($value[2]) != '物业类别' || trim($value[3]) != '物业名称' || trim($value[6]) != '租赁期限') {
                    return $this->jsonAdminResult([],10001,'表头格式错误');
                }
                continue;
            }

            if (trim($value[0]) == '') { // 编号的数据报错
                return $this->jsonAdminResult([],10001,'编号不能为空');
            }

            $import_data[] = [
                'number' => trim($value[0]),
                'company' => trim($value[1]),
                'property_type' => trim($value[2]),
                'property_name' => trim($value[3]),
                'address' => trim($value[4]),
                'area' => trim($value[5]),
                'term' => trim($value[6]),
                'rent' => trim($value[7]),
                'notes' => trim($value[8])
            ];
        }

        if (empty($import_data)) { // 空，则直接返回
            return $this->jsonAdminResultWithLog($request);
        }

        $insert_data = [];
        $time = date('Y-m-d H:i:s');
        foreach ($import_data as $value) {
            $info = $mProperty->where('number', $value['number'])->first();
            $info = $this->dbResult($info);
            if (!empty($info)) { // 更新
                $value['updated_at'] = $time;
                $mProperty->where('id', $info['id'])->update($value);
            } else { // 新增
                $value['created_at'] = $time;
                $value['updated_at'] = $time;
                $insert_data[] = $value;
            }
        }

        if (!empty($insert_data)) {
            $mProperty->insert($insert_data);
        }

        return $this->jsonAdminResultWithLog($request);
    }

    /**
     * @name 导出excel
     * @Post("/lv/property/exportExcel")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function exportExcel(Request $request, Property $mProperty)
    {
        $params = $request->all();

        $where = [];
        $where[] = ['is_del', '=', 0];
        // 编号
        if (!empty($params['number'])){
            $where[] = ['number', 'like', '%' . $params['number'] . '%'];
        }
        // 物业所属公司
        if (!empty($params['company'])){
            $where[] = ['company', 'like', '%' . $params['company'] . '%'];
        }
        // 物业名称
        if (!empty($params['property_name'])){
            $where[] = ['property_name', 'like', '%' . $params['property_name'] . '%'];
        }
        // 地址
        if (!empty($params['address'])){
            $where[] = ['address', 'like', '%' . $params['address'] . '%'];
        }

        $list = $mProperty->where($where)->orderBy('id', 'desc')->get();
        $list = $this->dbResult($list);
        if (empty($list)) {
            return $this->jsonAdminResult([],10001,'无可导出数据');
        }

        $exportData = [];
        $exportData[] = [
            '编号',
            '物业所属公司',
            '物业类别',
            '物业名称',
            '地址',
            '经营面积(㎡)',
            '租赁期限',
            '租金(元/月)',
            '备注'
        ];
        foreach ($list as $value) {
            $exportData[] = [
                $value['number'],
                $value['company'],
                $value['property_type'],
                $value['property_name'],
                $value['address'],
                $value['area'],
                $value['term'],
                $value['rent'],
                $value['notes']
            ];
        }

        $rootDir = config('filesystems.disks.public.root');
        $file_name = '物业列表';
        $sendfilePath = 'admin/' . $request->userId . '/excel/' . $file_name . '.xls';
        $tmpFileName = $rootDir . $sendfilePath;
        if (is_file($tmpFileName)) {
            unlink($tmpFileName);
        }
        $exportExcel = new PropertyExport($exportData);
        Excel::store($exportExcel, $sendfilePath, 'public', \Maatwebsite\Excel\Excel::XLS);

        $excelUrl = config('filesystems.disks.public.url') . $sendfilePath;

        return $this->jsonAdminResult(['excelUrl' => $excelUrl]);
    }

    /**
     * @name 导出图片
     * @Post("/lv/property/exportImage")
     * @Version("v1")
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     **/
    public function exportImage(Request $request, Property $mProperty)
    {
        $params = $request->all();

        $where = [];
        $where[] = ['is_del', '=', 0];
        // 编号
        if (!empty($params['number'])){
            $where[] = ['number', 'like', '%' . $params['number'] . '%'];
        }
        // 物业所属公司
        if (!empty($params['company'])){
            $where[] = ['company', 'like', '%' . $params['company'] . '%'];
        }
        // 物业名称
        if (!empty($params['property_name'])){
            $where[] = ['property_name', 'like', '%' . $params['property_name'] . '%'];
        }
        // 地址
        if (!empty($params['address'])){
            $where[] = ['address', 'like', '%' . $params['address'] . '%'];
        }

        $list = $mProperty->where($where)->orderBy('id', 'desc')->get();
        $list = $this->dbResult($list);
        if (empty($list)) {
            return $this->jsonAdminResult([],10001,'无可导出数据');
        }

        $all_images = [];
        foreach ($list as $value) {
            $temp_images = json_decode($value['images'], true) ?? [];
            if (!empty($temp_images)) {
                $all_images[trim($value['number'])] = $temp_images;
            }
        }
        if (empty($all_images)) {
            return $this->jsonAdminResult([], 10001, '无可导出图片');
        }

        // 生成zip文件
        $tmp = config('filesystems.disks.tmp.root') . 'admin/' . $request->userId . '/zip/';
        if (!is_dir($tmp)){
            mkdir($tmp,0777,true);
        }

        $zipName = sprintf("%s%s.zip", $tmp, '物业列表');
        if (is_file($zipName)) {
            unlink($zipName);
        }

        $image_url = config('filesystems.disks.tmp.root');
        file_put_contents($zipName,'');
        $zip = new \ZipArchive();
        $tmpFile = [];
        $tmpDir = [];
        if ($zip->open($zipName) === TRUE) {
            foreach ($all_images as $key => $value) {
                if (!is_dir($tmp . $key)){
                    mkdir($tmp . $key,0777,true);
                }
                $tmpDir[] = $tmp . $key;
                foreach ($value as $v) {
                    $fileName = substr($v, strrpos($v, '/') + 1);
                    file_put_contents($tmp . $key . '/' . $fileName, file_get_contents($image_url . $v));
                    $zip->addFile($tmp . $key . '/' . $fileName, $key . '/' . $fileName);
                    $tmpFile[] = $tmp . $key . '/' . $fileName;
                }
            }
        } else {
            return $this->jsonAdminResult([], 10001, '打包异常');
        }
        $zip->close();

        if (!empty($tmpFile)) {
            foreach ($tmpFile as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        if (!empty($tmpDir)) {
            foreach ($tmpDir as $dir) {
                if (is_dir($dir)) {
                    rmdir($dir);
                }
            }
        }

        $zip = config('filesystems.disks.tmp.url') . 'admin/' . $request->userId . '/zip/物业列表.zip';
        return $this->jsonAdminResult(['zip' => $zip]);
    }

    /**
     * @name 上传图片
     * @Post("/lv/property/uploadImage")
     * @Versions({"v1"})
     * @PermissionWhiteList
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImage(Request $request, Property $mProperty)
    {
        $file = $request->file('file');
        $tmpFile = '';
        if (!empty($file)) {
            $upload = new Upload();
            $tmpFile = $upload->uploadToTmp($file, 'admin/' . $request->userId . '/image/');
        }

        if ($tmpFile) {
            return $this->jsonAdminResult([
                'file' => config('filesystems.disks.tmp.url') . $tmpFile
            ]);
        } else {
            return $this->jsonAdminResult([],10001,'上传失败');
        }
    }

    /**
     * @name 保存图片
     * @Post("/lv/property/saveImage")
     * @Versions({"v1"})
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveImage(Request $request, Property $mProperty)
    {
        $params = $request->all();

        $id = $params['id'] ?? 0;
        $images = $params['images'] ?? [];

        if (empty($id)) {
            return $this->jsonAdminResult([],10001,'参数错误');
        }

        // 去掉域名前缀
        $urlPre = config('filesystems.disks.tmp.url');
        foreach ($images as $key => $value) {
            $images[$key] = str_replace($urlPre, '', $value);
        }

        $res = $mProperty->where('id', $id)->update(['images' => json_encode($images)]);
        if ($res !== false) {
            return $this->jsonAdminResultWithLog($request);
        } else {
            return $this->jsonAdminResult([],10001,'操作失败');
        }
    }
}
