<?php

use App\Http\Controllers\ProductsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PromotionController;

//* Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'loginUser']);

//* Google routes
Route::get('auth/google', [AuthController::class, 'redirectToGoogleAuth']);
Route::get('auth/google/callback', [AuthController::class, 'handleGoogleAuthCallback']);

//* Facebook routes
Route::get('auth/facebook', [AuthController::class, 'redirectToFaceBookAuth']);
Route::get('auth/facebook/callback', [AuthController::class, 'handleFacebookCallback']);

//* Products routes
Route::group(['prefix' => 'products'], function () {
   Route::get('/', [ProductsController::class, 'index']);
   Route::get('/{id}', [ProductsController::class, 'show']);
});

//* Discount routes
Route::get('/discount', [DiscountController::class, 'index']);
Route::get('/discounted-products', [DiscountController::class, 'getDiscountedProducts']);

//* Categories routes
Route::get('/category', [CategoryController::class, 'index']);
Route::get('/category/{id}', [CategoryController::class, 'show']);
Route::get('/query-categories', [CategoryController::class, 'queryCategories']);
Route::get('/query-multiple-categories', [CategoryController::class, 'queryMultipleCategories']);

//* Brand routes
Route::get('/brand', [BrandController::class, 'index']);
Route::get('/brand/{id}', [BrandController::class, 'show']);

//* Promotion
Route::get('/promotions', [PromotionController::class, 'index']);
Route::get('/promotions/{id}', [PromotionController::class, 'show']);
Route::post('/promotions', [PromotionController::class, 'store']);
Route::put('/promotions/update/{id}', [PromotionController::class, 'update']);
Route::delete('/promotions/delete/{id}', [PromotionController::class, 'destroy']);





//* Authenticated and Authorize routes
Route::group(['middleware' => ['auth:api']], function () {
   Route::get('auth/profile', [AuthController::class, 'profile']);


   //* Customer routes
   Route::group(['middleware' => ['auth:api', 'role:customer']], function () {
      Route::get('/cart', [CartController::class, 'viewCart']);
      Route::post('/cart/add', [CartController::class, 'addToCart']);
      Route::post('/cart/promotion', [CartController::class, 'addPromotionToCart']);
      Route::post('cart/update-quantity', [CartController::class, 'updateQuantityByProductId']);
      Route::delete('/cart/remove/{product_id}', [CartController::class, 'removeFromCart']);



      //** Place Order */
      Route::post('/orders', [OrderController::class, 'create']);
      Route::get('/orders/{id}', [OrderController::class, 'show']);

      //*Payment CheckOut
      Route::post('/create-checkout-session', [PaymentController::class, 'createCheckoutSession']);
   });


   //* Admin-only routes
   Route::group(['middleware' => 'role:admin'], function () {
      Route::get('/users', [AuthController::class, 'showUser']);

      //* Product API
      Route::group(['prefix' => 'products'], function () {
         Route::post('/', [ProductsController::class, 'store']);
         Route::put('/{id}', [ProductsController::class, 'update']);
         Route::delete('/{id}', [ProductsController::class, 'destroy']);
      });

      //* Discount routes
      Route::post('/products/{productId}/discounts', [DiscountController::class, 'attachDiscount']);
      Route::put('/discounts/{id}', [DiscountController::class, 'update']);
      Route::delete('/discounts/delete/{id}', [DiscountController::class, 'destroy']);

      //* Category routes
      Route::post('/category', [CategoryController::class, 'store']);
      Route::put('/category/{id}', [CategoryController::class, 'update']);
      Route::delete('/category/{id}', [CategoryController::class, 'destroy']);

      //* Brand routes
      Route::post('/brand', [BrandController::class, 'store']);
      Route::put('/brand/{id}', [BrandController::class, 'update']);
      Route::delete('/brand/{id}', [BrandController::class, 'destroy']);


      //* Order Approval
      Route::get('/orders/approve/detail', [OrderController::class, 'index']);
      Route::get('/orders/status/find', [OrderController::class, 'filterStatus']);
      Route::post('orders/{id}/approve', [OrderController::class, 'approve']);
      Route::post('orders/{id}/reject', [OrderController::class, 'reject']);
   });
});
