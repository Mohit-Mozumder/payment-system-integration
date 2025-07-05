<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DonationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [DonationController::class, 'index'])->name('donations.form');
Route::post('/donate', [DonationController::class, 'store'])->name('donations.store');
Route::get('/donations', [DonationController::class, 'list'])->name('donations.list');
Route::post('/donations/{donation}/refund', [DonationController::class, 'refund'])->name('donations.refund');
