<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SalesController;
use App\Http\Controllers\Api\ProjectController;
use Illuminate\Support\Facades\Route;
use Termwind\Components\Raw;

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
    Route::get('/reports/all', [ProjectController::class, 'getAllReports']);
    Route::get('/master-data/{type}', [ProjectController::class, 'getMasterDataByType']);
    Route::post('/master-data/{type}', [ProjectController::class, 'storeMaster']);
    Route::put('/master-data/{type}/{id}', [ProjectController::class, 'updateMaster']);
    Route::delete('/master-data/{type}/{id}', [ProjectController::class, 'deleteMaster']);
    Route::get('/get-master-data', [ProjectController::class, 'getMasterData']);
    Route::put('/projects/detail/{id}', [ProjectController::class, 'updateProjectDetail']);

   // --- PROJECT TASKS / AKTIVTY ---

    // 1. Untuk menambah task baru
    Route::post('/project-tasks', [ProjectController::class, 'storeTask']);
    Route::put('/project-tasks/{id}/toggle', [ProjectController::class, 'toggleTask']);
    Route::put('/project-tasks/{id}', [ProjectController::class, 'updateTask']);
    Route::delete('/project-tasks/{id}', [ProjectController::class, 'deleteTask']);
    // --- PROJECT workorder ---
    Route::get('/work-orders', [ProjectController::class, 'getWorkOrders']);
    Route::post('/work-orders', [ProjectController::class, 'storeWorkOrder']);
    Route::put('/work-orders/{id}', [ProjectController::class, 'updateWorkOrder']);
    Route::delete('/work-orders/{id}', [ProjectController::class, 'deleteWorkOrder']);
    Route::post('/projects/{id}/team', [ProjectController::class, 'addTeamMember']);
     // Route untuk hapus member dari project
    Route::delete('/projects/{projectId}/team/{userId}', [ProjectController::class, 'removeTeamMember']);

    Route::post('/project-productions', [ProjectController::class, 'storeProduction']);
    Route::delete('/project-productions/{id}', [ProjectController::class, 'deleteProduction']);

    Route::delete('/project-documents/{id}', [ProjectController::class, 'deleteDocument']);
    Route::post('/project-documents', [ProjectController::class, 'storeDocument']);

    Route::post('/project-supports', [ProjectController::class, 'storeSupport']);
    Route::put('/project-supports/{id}/status', [ProjectController::class, 'updateSupportStatus']);
    Route::delete('/project-supports/{id}', [ProjectController::class, 'deleteSupport']);

    Route::post('/project-marketings', [ProjectController::class, 'storeMarketing']);
    Route::delete('/project-marketings/{id}', [ProjectController::class, 'deleteMarketing']);

    // --- PROJECT PURCHASING ---
    Route::post('/project-purchasings', [ProjectController::class, 'storePurchasing']);
    Route::delete('/project-purchasings/{id}', [ProjectController::class, 'deletePurchasing']);
});