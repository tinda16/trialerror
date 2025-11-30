<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\WorkflowController; // <- TAMBAHAN: untuk workflow approval

Route::get('/', fn() => redirect()->route('dashboard'));

// ====================
// ROUTE YANG BUTUH LOGIN
// ====================
Route::middleware(['auth'])->group(function () {

    // ==================== Dashboard ====================
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/upload', [DashboardController::class, 'store'])->name('dashboard.upload');

    // ==================== File CRUD + View/Download ====================
    Route::resource('files', FileController::class);
    Route::get('files/{file}/view', [FileController::class, 'view'])->name('files.view');
    Route::get('files/{file}/download', [FileController::class, 'download'])->name('files.download');
    Route::get('/my-files', [FileController::class, 'myFiles'])->name('files.myfiles');
    Route::post('/files/{file}/favorite', [FileController::class, 'toggleFavorite'])->name('files.favorite');
    Route::get('/favorites', [FileController::class, 'favorites'])->name('files.favorites');

    // ==================== Profile ====================
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ==================== Kategori & Folder ====================
    Route::get('/category/{id}', [DashboardController::class, 'showCategory'])->name('category.show');
    Route::put('/category/{id}', [DashboardController::class, 'updateCategory'])->name('category.update');

    Route::get(
        '/category/{category}/folders/{folder}',
        [DashboardController::class, 'showFolder']
    )->name('category.folder');

    Route::post('/categories/{category}/folders', [FolderController::class, 'store'])->name('folders.store');
    Route::put('/folders/{id}', [FolderController::class, 'update'])->name('folders.update');
    Route::delete('/folders/{id}', [FolderController::class, 'destroy'])->name('folders.destroy');


    // ==================== AKSES KHUSUS MANAGER ====================
    Route::middleware(['role:manager'])->group(function () {

        // ========== WORKFLOW APPROVAL (MANAGER) ==========
        Route::get('/workflow/approvals', [WorkflowController::class, 'index'])
            ->middleware('perm:workflow.view')
            ->name('workflow.index');

        Route::post('/workflow/approve/{file}', [WorkflowController::class, 'approve'])
            ->middleware('perm:workflow.approve')
            ->name('workflow.approve');

        Route::post('/workflow/reject/{file}', [WorkflowController::class, 'reject'])
            ->middleware('perm:workflow.approve')
            ->name('workflow.reject');

        // ========== Halaman yang hanya boleh manager (tetap seperti awal) ==========
        Route::get('/users', fn() => view('welcome'))->name('users.index');
        Route::get('/audit-logs', fn() => view('welcome'))->name('audit-logs.index');
        Route::get('/roles', fn() => view('welcome'))->name('roles.index');
        Route::get('/customers', fn() => view('welcome'))->name('customers.index');
        Route::get('/transactions', fn() => view('welcome'))->name('transactions.index');
        Route::get('/kpi', fn() => view('welcome'))
            ->middleware('perm:kpi.view')
            ->name('kpi.index');
    });

    // ==================== AKSES KHUSUS STAFF ====================
    Route::middleware(['role:staff'])->group(function () {

        // ========== WORKFLOW APPROVAL (STAFF) ==========
        // Staff bisa lihat status submission mereka
        Route::get('/workflow/my-submissions', [WorkflowController::class, 'mySubmissions'])
            ->name('workflow.my-submissions');

        // Staff submit file untuk di-approve manager
        Route::post('/workflow/submit/{file}', [WorkflowController::class, 'submit'])
            ->name('workflow.submit');

        // Contoh halaman khusus staff (tetap dipertahankan)
        Route::get('/documents/upload', fn() => view('welcome'))
            ->middleware('perm:documents.upload')
            ->name('documents.create');
    });
});

require __DIR__ . '/auth.php';
