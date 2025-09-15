<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Authcontroller;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\AnalysisController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ColorController;
use App\Http\Controllers\user\FavoriteController;
use App\Http\Controllers\user\CartController;
use App\Http\Controllers\user\OrderController;
use App\Http\Controllers\user\ProductController as UserProductController;

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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/register' , [AuthController::class , 'register']);
Route::post('/login' , [Authcontroller::class , 'login']);
Route::get('user/products' , [UserProductController::class , 'allProducts']);
Route::get('home' , [UserProductController::class , 'home']);

Route::middleware('api' , 'auth:sanctum')->group(function () {

    Route::post('/logout' , [Authcontroller::class , 'logout']);
    Route::patch('/user/profile' , [Authcontroller::class , 'update']);

    Route::get('/favorites' , [FavoriteController::class , 'index']);
    Route::post('/favorites' , [FavoriteController::class , 'store']);
    Route::delete('/favorites/clear' , [FavoriteController::class , 'clear']);
    Route::delete('/favorites/{id}' , [FavoriteController::class , 'destroy']);


    Route::get('/carts' , [CartController::class , 'index']);
    Route::post('/carts' , [CartController::class , 'store']);
    Route::patch('/carts/{id}' , [CartController::class , 'update']);
    Route::delete('/carts/clear' , [CartController::class , 'clear']);
    Route::delete('/carts/{id}' , [CartController::class , 'destroy']);


    Route::post('user/orders' , [OrderController::class , 'store']);
    Route::get('user/orders' , [OrderController::class , 'index']);
    Route::get('user/orders/{id}' , [OrderController::class , 'show']);
    Route::patch('user/orders/{id}' , [OrderController::class , 'cancel']);
    Route::post('user/orders/{id}/return' , [OrderController::class , 'return']);





    Route::middleware('admin')->group(function () {

        Route::get('/user' , [Authcontroller::class , 'user']);

        Route::get('/products' , [ProductController::class , 'index']);
        Route::post('/products' , [ProductController::class , 'store']);
        Route::get('/products/{product}' , [ProductController::class , 'show']);
        Route::put('/products/{product}' , [ProductController::class , 'update']);
        Route::delete('/products/{product}' , [ProductController::class , 'destroy']);

        Route::get('/colors', [ColorController::class, 'index']);

        Route::get('/orders' , [AdminOrderController::class , 'index']);
        Route::get('/orders/{id}' , [AdminOrderController::class , 'show']);
        Route::patch('/orders/{id}' , [AdminOrderController::class , 'update']);
        Route::patch('/orders/{id}/return', [AdminOrderController::class, 'handleReturnRequest']);
        Route::delete('/orders/{id}' , [AdminOrderController::class , 'destroy']);

        Route::get('/analysis' , [AnalysisController::class , 'index']);

        Route::get('/users' , [UserController::class , 'users']);

    });
});

