<?php

use App\Http\Controllers\OrganizationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('organizations', [OrganizationController::class, 'index']);
    Route::get('organizations/{id}', [OrganizationController::class, 'show'])->whereUuid('id');
});
