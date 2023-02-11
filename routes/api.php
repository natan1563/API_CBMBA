<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserAvatarController;
use App\Http\Controllers\UserController;
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
Route::delete('/auth', [AuthController::class, 'logout'])->middleware('api.verify.auth');

Route::patch('/avatar', [UserAvatarController::class, 'updateProfileImage'])->middleware('api.verify.auth');
Route::get('/avatar/{imageName}', [UserAvatarController::class, 'showProfile']);
