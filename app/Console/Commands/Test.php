<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;


class Test extends Command
{
    protected $signature = 'Test';

    protected $description = '获取shop的token信息';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {

    }
}
