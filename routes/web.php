<?php

use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Public Portal
|--------------------------------------------------------------------------
*/

Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/doctors', [PublicController::class, 'doctors'])->name('doctors.index');
Route::get('/doctors/{doctor}', [PublicController::class, 'doctorProfile'])->name('doctors.show');
Route::get('/departments/{department}', [PublicController::class, 'departmentDetail'])->name('departments.show');
