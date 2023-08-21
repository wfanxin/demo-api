<?php

namespace App\Model\Admin;

use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\FormatTrait;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class Property extends Model
{
    use FormatTrait;
    public $table = 'propertys';
}
