<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SalesController;
use App\Http\Controllers\Api\ProjectController;
use Illuminate\Support\Facades\Route;
use Termwind\Components\Raw;
use Illuminate\Support\Facades\DB;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // --- PROJECT MANAGEMENT ---
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/projects/{id}', [ProjectController::class, 'show']);
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);
    Route::put('/projects/{id}/assign-company', [ProjectController::class, 'assignCompany']);
    Route::put('/projects/{id}/unassign-company', [ProjectController::class, 'unassignCompany']);
    
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
    Route::put('/users/{id}', [AuthController::class, 'update']);
    Route::delete('/users/{id}', [AuthController::class, 'destroy']);

    // --- ANALYTICS ---
    Route::get('/notifications', [ProjectController::class, 'getNotifications']);
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

    // Route::post('/projects/{id}/team', [ProjectController::class, 'addTeamMember']);
    // Route::delete('/projects/{projectId}/team/{userId}', [ProjectController::class, 'removeTeamMember']);
    // Route untuk hapus member dari project

    Route::post('/project-productions', [ProjectController::class, 'storeProduction']);
    Route::delete('/project-productions/{id}', [ProjectController::class, 'deleteProduction']);

    // Route::delete('/project-documents/{id}', [ProjectController::class, 'deleteDocument']);
    // Route::post('/project-documents', [ProjectController::class, 'storeDocument']);
    Route::get('/project-documents', [ProjectController::class, 'indexDocuments']);
    Route::post('/project-documents', [ProjectController::class, 'storeDocuments']);
    Route::delete('/project-documents/{id}', [ProjectController::class, 'destroyDocuments']);

    Route::post('/project-supports', [ProjectController::class, 'storeSupport']);
    Route::put('/project-supports/{id}/status', [ProjectController::class, 'updateSupportStatus']);
    Route::delete('/project-supports/{id}', [ProjectController::class, 'deleteSupport']);

    Route::post('/project-marketings', [ProjectController::class, 'storeMarketing']);
    Route::delete('/project-marketings/{id}', [ProjectController::class, 'deleteMarketing']);

    // --- PROJECT PURCHASING ---
    Route::post('/project-purchasings', [ProjectController::class, 'storePurchasing']);
    Route::delete('/project-purchasings/{id}', [ProjectController::class, 'deletePurchasing']);
   // --- 1. TEAMWORK & RESOURCE MANAGEMENT ---
    Route::get('/teamwork/summary', [ProjectController::class, 'getTeamworkSummary']);
    Route::get('/teamwork/top-outstanding', [ProjectController::class, 'getTopOutstanding']);
    Route::post('/teamwork/finance', [ProjectController::class, 'storeTeamFinance']);
    Route::put('/teamwork/finance/{id}/settle', [ProjectController::class, 'settleTeamFinance']);

    // --- 2. COMPANY / PT MANAGEMENT ---
    Route::get('/companies', [ProjectController::class, 'getCompanies']);
    Route::post('/companies', [ProjectController::class, 'storeCompany']);
    Route::put('/companies/{id}', [ProjectController::class, 'updateCompany']);
    Route::delete('/companies/{id}', [ProjectController::class, 'destroyCompany']);
    // Menguhubungkan Project dengan Companies
    Route::get('/projects/{id}/companies', [ProjectController::class, 'showProjectCompanies']);
    Route::post('/projects/{id}/sync-companies', [ProjectController::class, 'syncProjectCompanies']);

    Route::post('/tasks/{taskId}/like', [ProjectController::class, 'toggleLike']);
    Route::post('/tasks/{taskId}/comment', [ProjectController::class, 'postComment']);

    // --- 3. FINANCE & ACCOUNTING ---
    Route::get('/finance/pt-performance', [ProjectController::class, 'getPTPerformance']);
    Route::post('/project-invoices', [ProjectController::class, 'storeInvoice']);
    Route::put('/project-invoices/{id}/status', [ProjectController::class, 'updateInvoiceStatus']);

    // --- 4. CATALOG ---
    Route::get('/finance/consolidated', [ProjectController::class, 'getConsolidatedFinance']);
    Route::get('/accounting/coas', [ProjectController::class, 'getCOAs']);
    Route::post('/accounting/coas', [ProjectController::class, 'storeCOA']);
    // Gunakan yang ini agar sesuai dengan pemanggilan di Vue
    Route::get('/accounting/ledger', [ProjectController::class, 'getJournals']); 
    Route::get('/finance/cashflow', [ProjectController::class, 'getCashFlow']);

    // Project Invoices
    Route::post('/project-invoices', [ProjectController::class, 'storeInvoice']);
    Route::put('/project-invoices/{id}/status', [ProjectController::class, 'updateInvoiceStatus']);

    // --- 3. FINANCE & ACCOUNTING ---
    Route::get('/finance/pt-performance', [ProjectController::class, 'getPTPerformance']);
    
    // ---> TAMBAHKAN 2 BARIS INI <---
    Route::get('/finance/transactions', [ProjectController::class, 'getTransactions']);
    Route::post('/finance/transactions', [ProjectController::class, 'storeTransaction']);
    Route::put('/finance/transactions/{id}', [ProjectController::class, 'updateTransaction']); // <--- TAMBAHKAN INI
    Route::delete('/finance/transactions/{id}', [ProjectController::class, 'destroyTransaction']); // <--- TAMBAHKAN INI
    // -------------------------------
    Route::put('/accounting/coas/{id}', [ProjectController::class, 'updateCOA']);
    Route::delete('/accounting/coas/{id}', [ProjectController::class, 'deleteCOA']);
    // --- BANKING MASTER ---
    Route::get('/finance/banks', [ProjectController::class, 'getBanks']);
    Route::post('/finance/banks', [ProjectController::class, 'storeBank']);
    Route::put('/finance/banks/{id}', [ProjectController::class, 'updateBank']);
    Route::delete('/finance/banks/{id}', [ProjectController::class, 'deleteBank']); 
});