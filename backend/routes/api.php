<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SalesController;
use App\Http\Controllers\Api\ProjectController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Project Solutions Backoffice
|--------------------------------------------------------------------------
*/

// 1. Authentication (Public)
Route::post('/login', [AuthController::class, 'login']);

// 2. Protected Routes (Harus Login & Pakai Token)
Route::middleware('auth:sanctum')->group(function () {

    // --- MENU 1: SALES LEADS (MARKETING) ---
    Route::get('/sales/leads', [SalesController::class, 'index']);
    Route::post('/sales/leads', [SalesController::class, 'store']);
    Route::get('/sales/leads/{id}', [SalesController::class, 'show']);
    Route::put('/sales/leads/{id}', [SalesController::class, 'update']);
    Route::delete('/sales/leads/{id}', [SalesController::class, 'destroy']);

    // --- MENU 2: PROJECT MANAGEMENT (WORKS) ---
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/projects/{id}', [ProjectController::class, 'show']);
    Route::put('/projects/{id}/progress', [ProjectController::class, 'updateProgress']);
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);
    Route::get('/work-categories', [ProjectController::class, 'getCategories']);
    Route::post('/work-categories', [ProjectController::class, 'storeCategory']);
    Route::get('/projects/category/{id}', [ProjectController::class, 'getByWorkCategory']);
    // --- MENU 9: TEAMWORK (USER MANAGEMENT) ---
    Route::get('/users', [AuthController::class, 'index']);
    // Sesuaikan dengan request di Vue: api.post('/users/register')
    Route::post('/users/register', [AuthController::class, 'register']); 
    Route::put('/users/{id}/role', [AuthController::class, 'updateRole']);
    Route::delete('/users/{id}', [AuthController::class, 'destroy']);

    // --- UTILS & ANALYSIS ---
    Route::get('/audit-logs', [ProjectController::class, 'getLogs']);
    Route::get('/works/stats', [ProjectController::class, 'getStats']); // Endpoint data Chart
});