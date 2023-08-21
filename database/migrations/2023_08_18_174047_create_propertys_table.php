<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePropertysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('propertys', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('number', 100)->comment('编号');
            $table->string('company', 255)->comment('物业所属公司');
            $table->string('property_type', 100)->comment('物业类别');
            $table->string('property_name', 255)->comment('物业名称');
            $table->string('address', 500)->comment('地址');
            $table->string('area', 100)->comment('经营面积(㎡)');
            $table->string('term', 100)->comment('租赁期限');
            $table->string('rent', 100)->comment('租金(元/月)');
            $table->text('notes')->comment('备注');
            $table->text('images')->comment('图片组');
            $table->tinyInteger('is_del')->default(0)->comment('删除：0：否，1：是');
            $table->timestamps();
        });
        \Illuminate\Support\Facades\DB::statement("alter table `propertys` comment '物业数据表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('propertys');
    }
}
