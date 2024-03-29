<?php

namespace App\Model\Api;

use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\FormatTrait;
use Illuminate\Support\Facades\DB;

class Member extends Model
{
    use FormatTrait;
    public $table = 'members';

    /**
     * 获取等级配置
     * @return array
     */
    public function getLevelList() {
        $level_list = config('global.level_list');

        $count = count($level_list);
        $level_item = $level_list[$count - 1]; // 获取最后一项

        while ($count <= 20) {
            $level_item['value'] = $count;
            $level_list[$count] = $level_item;
            $count++;
        }

        $level_list = array_column($level_list, 'label', 'value');

        return $level_list;
    }

    /**
     * 注册获取上级id
     * @param $invite_uid
     * @return mixed
     */
    public function getPuid($invite_uid) {
        $list = $this->where('id', $invite_uid)->get();
        $list = $this->dbResult($list);
        while (1) {
            $temp_list = [];
            foreach ($list as $value) {
                $child_list = $this->where('p_uid', $value['id'])->get();
                $child_list = $this->dbResult($child_list);
                if (count($child_list) < 2) { // 找到了
                    return $value['id'];
                }

                $temp_list = array_merge($temp_list, $child_list);
            }
            $list = $temp_list;
        }
    }

    /**
     * 删除会员
     * @param $data
     * @return mixed
     */
    public function delMember($data) {
        return $this->where('id', $data['id'])->delete();
    }
}
