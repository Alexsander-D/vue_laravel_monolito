<?php

use App\Http\Controllers\Spatie\RolesController;
use App\Http\Controllers\Spatie\PermissionsController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => config('jetstream.middleware', ['web'])], function () {
    $authMiddleware = config('jetstream.guard')
        ? 'auth:' . config('jetstream.guard')
        : 'auth';

    $authSessionMiddleware = config('jetstream.auth_session', false)
        ? config('jetstream.auth_session')
        : null;

    Route::group(['middleware' => array_values(array_filter([$authMiddleware, $authSessionMiddleware]))], function () {
        // Roles...
        if (true) {
            Route::get('/roles/create', [RolesController::class, 'create'])->name('roles.create');
            // Route::get('/roles/show', [RolesController::class, 'show'])->name('roles.show');

            // Rotas para gerenciar roles
            Route::put('/roles/store', [RolesController::class, 'store'])->name('roles.store');

            // Rotas para gerenciar permissões
            Route::put('/permissions/store', [PermissionsController::class, 'store'])->name('permissions.store');
            Route::put('/permissions/storePermissionRoleRelation', [PermissionsController::class, 'storePermissionRoleRelation'])->name('permissions.storePermissionRoleRelation');

            Route::post('/roles/{team}', [RolesController::class, 'update'])->name('roles.update');
            Route::delete('/roles/{team}', [RolesController::class, 'destroy'])->name('roles.destroy');
        }
    });
});
