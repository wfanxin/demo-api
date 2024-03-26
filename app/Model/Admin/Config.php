<?php

namespace App\Model\Admin;

use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    //

    /**
     * 获取选项配置
     * @return array
     */
    public function getOption() {
        $content = $this->where('name', 'option')->value('content');
        if (empty($content)) {
            return [];
        }

        $option = [];
        $content = json_decode($content, true);
        foreach ($content as $key => $value) {
            $temp_value = $value['value'] ?? '';
            $temp_value = str_replace('，', ',', $temp_value);
            $temp_arr = explode(',', $temp_value);
            $temp_option = [];
            foreach ($temp_arr as $v) {
                $temp_option[] = ['label' => $v, 'value' => $v];
            }
            $option[$key . '_list'] = $temp_option;

        }

        return $option;
    }
}
