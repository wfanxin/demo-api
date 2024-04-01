<?php

namespace App\Console\Commands;

use App\Http\Traits\FormatTrait;
use App\Model\Api\Member;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;


class AnalysisPdf extends Command
{
    use FormatTrait;

    protected $signature = 'AnalysisPdf';

    protected $description = '解析pdf文件';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->_log('解析pdf文件开始');

        $fileTmp_realpath = public_path('/tmp/') . '箱标.pdf';
        $appPath = app_path();
        $result = shell_exec("python3 {$appPath}/Common/pyPdf/index.py {$fileTmp_realpath}");
        $pages = json_decode(trim($result), true);
        if (! is_array($pages)) {
            return $this->ArrayResult([], 10001, 'pdf解析失败',true);
        }

        dd($pages);

        $this->_log('解析pdf文件结束');
    }
    public function _log($data) {
        if (is_array($data)) {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        @file_put_contents(storage_path('logs/') . $this->signature . '-' . date('Y-m-d') . '.log', date('Y-m-d H:i:s') . ':' . $data . "\n", FILE_APPEND);
    }
}
