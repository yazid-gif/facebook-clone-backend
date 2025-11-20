<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\PostController;
use Illuminate\Support\Facades\Route;

// ==========================================
// Routes d'authentification avec limite STRICTE
// ==========================================
Route::prefix('auth')->middleware('throttle:auth')->group(function () {
    // Maximum 5 tentatives par minute
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Routes protégées d'authentification
Route::middleware('auth:api')->prefix('auth')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
});

// ==========================================
// Routes Posts avec rate limiting personnalisé
// ==========================================
// Routes publiques (sans authentification)
Route::middleware('throttle:posts')->group(function () {
    Route::get('posts', [PostController::class, 'index']);
    Route::get('posts/{post}', [PostController::class, 'show']);
});

// Routes protégées (authentification requise)
Route::middleware(['auth:api', 'throttle:posts'])->group(function () {
    Route::post('posts', [PostController::class, 'store']);
    Route::put('posts/{post}', [PostController::class, 'update']);
    Route::patch('posts/{post}', [PostController::class, 'update']);
    Route::delete('posts/{post}', [PostController::class, 'destroy']);
});

// ==========================================
// Routes Commentaires (nested resources)
// ==========================================
// Routes publiques (sans authentification)
Route::middleware('throttle:posts')->group(function () {
    Route::get('posts/{post}/commentaires', [CommentController::class, 'index']);
    Route::get('posts/{post}/commentaires/{comment}', [CommentController::class, 'show']);
});

// Routes protégées (authentification requise)
Route::middleware(['auth:api', 'throttle:posts'])->group(function () {
    Route::post('posts/{post}/commentaires', [CommentController::class, 'store']);
    Route::put('posts/{post}/commentaires/{comment}', [CommentController::class, 'update']);
    Route::patch('posts/{post}/commentaires/{comment}', [CommentController::class, 'update']);
    Route::delete('posts/{post}/commentaires/{comment}', [CommentController::class, 'destroy']);
});

// ==========================================
// EXEMPLES : Limites Dynamiques par Endpoint
// ==========================================
// Vous pouvez aussi utiliser des limites inline directement sur les routes :
/*
Route::post('posts', [PostController::class, 'store'])
    ->middleware('throttle:10,1'); // 10 requêtes par 1 minute

Route::get('posts', [PostController::class, 'index'])
    ->middleware('throttle:100,1'); // 100 requêtes par 1 minute
*/
