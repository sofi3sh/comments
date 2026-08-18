<?php

\Illuminate\Support\Facades\Route::get('email/verify', [\App\Http\Controllers\Auth\EmailVerificationController::class, 'notice'])
    ->middleware($bpAuthMiddleware)
    ->name('verification.notice');

\Illuminate\Support\Facades\Route::post('email/verification-notification', [\App\Http\Controllers\Auth\EmailVerificationController::class, 'send'])
    ->middleware([$bpAuthMiddleware, 'throttle:6,1'])
    ->name('verification.send');

\Illuminate\Support\Facades\Route::get('email/verify/{id}/{hash}', [\App\Http\Controllers\Auth\EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');