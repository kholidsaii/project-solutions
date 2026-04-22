<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SalesController;
use App\Http\Controllers\Api\ProjectController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // --- PROJECT MANAGEMENT ---
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/projects/{id}', [ProjectController::class, 'show']);
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);
    
    // Task & Category
    Route::get('/work-categories', [ProjectController::class, 'getCategories']);
    Route::post('/work-categories', [ProjectController::class, 'storeCategory']);
    Route::get('/projects/category/{id}', [ProjectController::class, 'getByWorkCategory']);
    Route::post('/project-tasks', [ProjectController::class, 'storeTask']); // Perbaikan route
    Route::put('/project-tasks/{id}/toggle', [ProjectController::class, 'toggleTask']); // Perbaikan route

    // --- USER MANAGEMENT ---
    Route::get('/users', [AuthController::class, 'index']);
    Route::post('/users/register', [AuthController::class, 'register']); 
    Route::put('/users/{id}/role', [AuthController::class, 'updateRole']);
    Route::delete('/users/{id}', [AuthController::class, 'destroy']);

    // --- ANALYTICS ---
    Route::get('/works/stats', [ProjectController::class, 'getStats']);
    Route::get('/reports/all', [ProjectController::class, 'getAllReports']); // Untuk Assessment/Reports.vue
});