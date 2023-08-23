<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*********************************************/

$dingoApi = app(\Dingo\Api\Routing\Router::class);
$dingoApi->version("v1", [
    "middleware" => ["AdminToken", "CrossHttp"]
], function ($dingoApi) {

    // 物业管理
    $dingoApi->get("property/propertyList", \App\Http\Controllers\Admin\Property\IndexController::class."@propertyList")->name("property.propertyList");
    $dingoApi->post("property/addProperty", \App\Http\Controllers\Admin\Property\IndexController::class."@addProperty")->name("property.addProperty");
    $dingoApi->post("property/editProperty", \App\Http\Controllers\Admin\Property\IndexController::class."@editProperty")->name("property.editProperty");
    $dingoApi->post("property/delProperty", \App\Http\Controllers\Admin\Property\IndexController::class."@delProperty")->name("property.delProperty");
    $dingoApi->post("property/uploadFile", \App\Http\Controllers\Admin\Property\IndexController::class."@uploadFile")->name("property.uploadFile");
    $dingoApi->post("property/exportExcel", \App\Http\Controllers\Admin\Property\IndexController::class."@exportExcel")->name("property.exportExcel");
    $dingoApi->post("property/exportImage", \App\Http\Controllers\Admin\Property\IndexController::class."@exportImage")->name("property.exportImage");
    $dingoApi->post("property/uploadImage", \App\Http\Controllers\Admin\Property\IndexController::class."@uploadImage")->name("property.uploadImage");
    $dingoApi->post("property/saveImage", \App\Http\Controllers\Admin\Property\IndexController::class."@saveImage")->name("property.saveImage");

    // 用户
    $dingoApi->post("users/checkName", \App\Http\Controllers\Admin\System\UserController::class."@checkName")->name("users.checkName");
    $dingoApi->put("users/pwd", \App\Http\Controllers\Admin\System\UserController::class."@changePwd")->name("users.changePwd");
    $dingoApi->delete("users/batch", \App\Http\Controllers\Admin\System\UserController::class."@batchDestroy")->name("users.batchDestroy"); # 非resource应该放在resource上面
    $dingoApi->Resource("users", \App\Http\Controllers\Admin\System\UserController::class);

    // 权限
    $dingoApi->patch("permissions/{id}", \App\Http\Controllers\Admin\System\PermissionController::class."@edit")->name("permissions.edit");
    $dingoApi->get("permissions/total", \App\Http\Controllers\Admin\System\PermissionController::class."@total")->name("permissions.total");
    $dingoApi->get("permissions", \App\Http\Controllers\Admin\System\PermissionController::class."@index")->name("permissions.index");
    $dingoApi->put("permissions", \App\Http\Controllers\Admin\System\PermissionController::class."@update")->name("permissions.update");

    // 角色
    $dingoApi->get("roles/total", \App\Http\Controllers\Admin\System\RoleController::class."@total")->name("roles.total");
    $dingoApi->delete("roles/batch", \App\Http\Controllers\Admin\System\RoleController::class."@batchDestroy")->name("roles.batchDestroy");
    $dingoApi->Resource("roles", \App\Http\Controllers\Admin\System\RoleController::class);

    // 系统操作日志
    $dingoApi->get("logs", \App\Http\Controllers\Admin\System\LogController::class."@index")->name("logs.index");

    // 用户授权令牌 - 销毁
    $dingoApi->delete("tokens/{role}", \App\Http\Controllers\Admin\System\TokenController::class."@destroy")->name("tokens.destroy");

});

$dingoApi->version("v1", [
    "middleware" => ["CrossHttp"]
], function ($dingoApi) {
    // 用户授权令牌 - 获取
    $dingoApi->post("tokens", \App\Http\Controllers\Admin\System\TokenController::class."@store")->name("tokens.store");
});


