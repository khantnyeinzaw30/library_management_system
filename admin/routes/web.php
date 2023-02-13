<?php

use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReturningController;
use App\Http\Controllers\ShelfController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'login', 301);
Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::get('/register', [AuthController::class, 'register'])->name('register');

Route::middleware(['auth', 'admin'])->group(function () {
    // dashboard actions
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::delete('/dashboard/borrow_requests/{id}', [DashboardController::class, 'destroyRequest'])->name('borrow_requests.destroy');
    // users
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    // borrowings
    Route::resource('/borrowings', BorrowingController::class);
    // books
    Route::resource('/books', BookController::class);
    Route::post('/import-books', [BookController::class, 'import'])->name('books.import');
    Route::get('/export-books', [BookController::class, 'exportBooks'])->name('books.export');
    // categories
    Route::resource('/categories', CategoryController::class);
    // shelves
    Route::resource('/shelves', ShelfController::class);
    Route::get('/export-shelves', [ShelfController::class, 'exportShelves'])->name('shelves.export');
});