<?php

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function (){
    Route::get('conversations', [\App\Http\Controllers\ConversationsController::class, 'index']);
    Route::get('conversations/{conversation}', [\App\Http\Controllers\ConversationsController::class, 'show']);
    Route::post('conversations/{conversation}/participants', [\App\Http\Controllers\ConversationsController::class, 'addParticipant']);
    Route::delete('conversations/{conversation}/participants', [\App\Http\Controllers\ConversationsController::class, 'removeParticipant']);

    Route::put('conversations/{conversation}/read', [\App\Http\Controllers\ConversationsController::class, 'markAsRead']);

    Route::get('conversations/{id}/messages', [\App\Http\Controllers\MessagesController::class, 'index']);
    Route::post('messages', [\App\Http\Controllers\MessagesController::class, 'store'])->name('api.message.send');
    Route::delete('messages/{id}', [\App\Http\Controllers\MessagesController::class, 'destroy']);
});

// Route::post('messages', [\App\Http\Controllers\MessagesController::class, 'store'])->name('api.message.send');
