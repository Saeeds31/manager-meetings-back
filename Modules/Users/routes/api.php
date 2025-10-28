<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\AuthController;
use Modules\Users\Http\Controllers\RolesController;
use Modules\Users\Http\Controllers\UsersController;

Route::middleware(['auth:sanctum'])->prefix('v1/admin')->group(function () {
    Route::apiResource('users', UsersController::class)->names('users');
    Route::post('v1/admin/users/{userId}/accept', [UsersController::class, 'acceptUser'])->name("acceptUser");
    Route::post('v1/admin/users/{userId}/reject', [UsersController::class, 'rejectUser'])->name("rejectUser");
    Route::apiResource('roles', RolesController::class)->names('roles');
    Route::get('/user-managers', [UsersController::class, 'managerIndex'])->name("managerIndex");
    Route::post('/user-managers/assign-roles', [RolesController::class, 'assignRoles'])->name("assignRoles");
    Route::get('/admin-info', [AuthController::class, 'adminInfo'])->name("adminInfo");
    Route::get('/admin-permissions', [AuthController::class, 'adminPermissions'])->name("adminPermissions");

});
Route::post('v1/admin/login', [AuthController::class, 'adminLogin'])->name("adminLogin");
Route::post('v1/admin/login-verify', [AuthController::class, 'adminVerify'])->name("adminVerify");
Route::middleware(['auth:sanctum'])->prefix('v1/front')->group(function () {
    Route::get('/user/profile', [UsersController::class, 'userProfile'])->name("userProfile");
    Route::get('/user/validity', [UsersController::class, 'userValidity'])->name("userValidity");
});
Route::prefix('v1/front')->group(function () {
    Route::post('/check-mobile', [AuthController::class, 'checkMobile']);
    Route::post('/send-otp', [AuthController::class, 'sendOtpAgain']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/register', [AuthController::class, 'register'])->name("register");
});
