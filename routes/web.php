<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QRLoginWS\QRLoginWSController;

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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::get('/qrtest', function () {
    return view('qrlogin.showqr');
});
Route::get('/qrscanner',[QRLoginWSController::class,'qrscanner'])->name('qrscanner');
Route::post('web/loginws', [QRLoginWSController::class,'loginWS']);//Check when passcode hasbeen received

require __DIR__.'/auth.php';
