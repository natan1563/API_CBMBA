<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::resource('/users', UserController::class, ['except' => 'store'])->middleware('api.verify.auth');
Route::post('/users', [UserController::class, 'store']);
Route::post('/auth', [AuthController::class, 'login']);
Route::delete('/auth', [AuthController::class, 'logout']);
