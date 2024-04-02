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
     * 删除会员
     * @param $data
     * @return mixed
     */
    public function delMember($data) {
        return $this->where('id', $data['id'])->delete();
    }
}
