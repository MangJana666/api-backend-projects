<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\StoriesController;
use App\Http\Controllers\CategoriesController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::get('/all-stories', [StoriesController::class, 'allStories'])->name('all-stories');
Route::get('/newest-stories', [StoriesController::class, 'getNewestStory'])->name('newest-stories');
Route::get('/story-by-category/{categoryId}', [StoriesController::class, 'storiesByCategory'])->name('story-by-category');
Route::get('/story-sort-by', [StoriesController::class, 'sortStory'])->name('story-sort-by');

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('users', UsersController::class)->parameters([
        'users' => 'id'
    ]);
    Route::put('/update-password/{id}', [UsersController::class, 'updatePassword'])->name('update-password');
    Route::put('/update-user-profile/{id}', [UsersController::class, 'updateUserProfile'])->name('update-user-profile');
    Route::post('/upload-avatar', [UsersController::class, 'uploadAvatar'])->name('upload-avatar');
    Route::apiResource('categories', CategoriesController::class)->parameters([
        'categories' => 'id'
    ]);
    // Route::apiResource('stories', StoriesController::class)->parameters([
    //     'stories' => 'id'
    // ]);
    // Route::put('/update-stories/{id}', [StoriesController::class, 'updateStories'])->name('update-stories');
    Route::apiResource('stories', StoriesController::class)->parameters([
        'stories' => 'id'
    ]);
    Route::get('my-profile/stories', [StoriesController::class, 'myStories'])->name('my-stories');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

    
