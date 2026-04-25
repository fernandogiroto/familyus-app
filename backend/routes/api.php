<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HouseController;
use App\Http\Controllers\PrizeController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [AuthController::class, 'updateProfile']);
    Route::post('/me/avatar', [AuthController::class, 'uploadAvatar']);

    // Houses
    Route::post('/houses', [HouseController::class, 'create']);
    Route::get('/houses/join/{code}', [HouseController::class, 'join']);
    Route::get('/houses/{house}', [HouseController::class, 'show']);
    Route::post('/houses/{house}/ready', [HouseController::class, 'setReady']);
    Route::post('/houses/{house}/start-date', [HouseController::class, 'setStartDate']);
    Route::get('/houses/{house}/poll', [HouseController::class, 'checkReady']);

    // Tasks
    Route::post('/houses/{house}/tasks', [TaskController::class, 'store']);
    Route::put('/houses/{house}/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/houses/{house}/tasks/{task}', [TaskController::class, 'destroy']);
    Route::post('/houses/{house}/tasks/{task}/doing', [TaskController::class, 'startDoing']);
    Route::delete('/houses/{house}/tasks/{task}/doing', [TaskController::class, 'cancelDoing']);
    Route::post('/houses/{house}/tasks/{task}/done', [TaskController::class, 'completeDoing']);
    Route::post('/houses/{house}/tasks/{task}/photo', [TaskController::class, 'uploadPhoto']);

    // Prizes
    Route::post('/houses/{house}/prizes', [PrizeController::class, 'store']);
    Route::delete('/houses/{house}/prizes/{prize}', [PrizeController::class, 'destroy']);
    Route::post('/houses/{house}/prizes/select', [PrizeController::class, 'selectWinnerPrize']);
    Route::get('/houses/{house}/weekly-reset', [PrizeController::class, 'checkWeeklyReset']);
});
