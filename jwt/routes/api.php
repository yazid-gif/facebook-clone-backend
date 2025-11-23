<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\UserController;
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
    Route::get('posts/search', [SearchController::class, 'search']);
    Route::get('posts/{post}', [PostController::class, 'show']);
    Route::get('posts/{post}/likes', [PostController::class, 'showLikes']);
});

// Routes protégées (authentification requise)
Route::middleware(['auth:api', 'throttle:posts'])->group(function () {
    Route::post('posts', [PostController::class, 'store']);
    Route::put('posts/{post}', [PostController::class, 'update']);
    Route::patch('posts/{post}', [PostController::class, 'update']);
    Route::delete('posts/{post}', [PostController::class, 'destroy']);
    
    // Routes pour attacher/détacher des tags
    Route::post('posts/{post}/tags', [PostController::class, 'attachTags']);
    Route::delete('posts/{post}/tags/{tag}', [PostController::class, 'detachTag']);
    
    // Routes pour liker/unliker
    Route::post('posts/{post}/like', [PostController::class, 'like']);
    Route::delete('posts/{post}/like', [PostController::class, 'unlike']);
    
    // Routes pour upload/supprimer l'image de couverture
    Route::post('posts/{post}/image', [PostController::class, 'uploadImage']);
    Route::delete('posts/{post}/image', [PostController::class, 'deleteImage']);
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
// Routes Catégories
// ==========================================
// Routes publiques (sans authentification)
Route::middleware('throttle:posts')->group(function () {
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{category}', [CategoryController::class, 'show']);
});

// Routes Editor et Admin (gestion des catégories)
Route::middleware(['auth:api', 'role:editor,admin', 'throttle:posts'])->group(function () {
    Route::post('categories', [CategoryController::class, 'store']);
    Route::put('categories/{category}', [CategoryController::class, 'update']);
    Route::patch('categories/{category}', [CategoryController::class, 'update']);
    Route::delete('categories/{category}', [CategoryController::class, 'destroy']);
});

// ==========================================
// Routes Tags
// ==========================================
// Routes publiques (sans authentification)
Route::middleware('throttle:posts')->group(function () {
    Route::get('tags', [TagController::class, 'index']);
    Route::get('tags/{tag}', [TagController::class, 'show']);
});

// Routes Editor et Admin (gestion des tags)
Route::middleware(['auth:api', 'role:editor,admin', 'throttle:posts'])->group(function () {
    Route::post('tags', [TagController::class, 'store']);
    Route::put('tags/{tag}', [TagController::class, 'update']);
    Route::patch('tags/{tag}', [TagController::class, 'update']);
    Route::delete('tags/{tag}', [TagController::class, 'destroy']);
});

// ==========================================
// Routes Admin uniquement
// ==========================================
Route::middleware(['auth:api', 'role:admin', 'throttle:posts'])->group(function () {
    // Suppression définitive d'un article
    Route::delete('posts/{post}/force', [PostController::class, 'forceDelete']);
    
    // Gestion des utilisateurs
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{user}', [UserController::class, 'show']);
    Route::put('users/{user}', [UserController::class, 'update']);
    Route::patch('users/{user}', [UserController::class, 'update']);
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
