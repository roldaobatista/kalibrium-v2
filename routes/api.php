<?php

declare(strict_types=1);

use App\Http\Controllers\Mobile\ForgotPasswordController as MobileForgotPasswordController;
use App\Http\Controllers\Mobile\LoginController as MobileLoginController;
use App\Http\Controllers\Mobile\MeController as MobileMeController;
use App\Http\Controllers\Mobile\SyncPhotoDownloadController;
use App\Http\Controllers\Mobile\SyncPhotoUploadController;
use App\Http\Controllers\Mobile\SyncPullController;
use App\Http\Controllers\Mobile\SyncPushController;
use Illuminate\Support\Facades\Route;

Route::post('/mobile/login', MobileLoginController::class)
    ->middleware(['mobile.tenant', 'mobile.login.throttle'])
    ->name('mobile.login');

Route::post('/mobile/password/forgot', MobileForgotPasswordController::class)
    ->middleware(['mobile.forgot.throttle'])
    ->name('mobile.password.forgot');

Route::middleware(['mobile.device.status', 'auth:sanctum'])->group(function (): void {
    Route::get('/mobile/me', MobileMeController::class)->name('mobile.me');
});

Route::middleware(['mobile.device.status', 'auth:sanctum', 'mobile.tenant.context'])->group(function (): void {
    Route::post('/mobile/sync/push', SyncPushController::class)->name('mobile.sync.push');
    Route::get('/mobile/sync/pull', SyncPullController::class)->name('mobile.sync.pull');
    Route::post('/mobile/sync/upload-photo', [SyncPhotoUploadController::class, 'upload'])->name('mobile.sync.photo.upload');
    Route::get('/mobile/sync/photo/{id}/signed-url', [SyncPhotoUploadController::class, 'signedUrl'])->name('mobile.sync.photo.signed-url');
    Route::get('/mobile/sync/photo/{id}/download', SyncPhotoDownloadController::class)->name('mobile.sync.photo.download');
});
