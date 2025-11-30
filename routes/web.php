<?php

use App\Http\Controllers\AdminControlller;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\WishlistCotroller;
use App\Http\Middleware\AuthAdmin;
use Illuminate\Support\Facades\Auth;


Auth::routes();

Route::get('/', [HomeController::class, 'index'])->name('home.index');
Route::get('/shop',[ShopController::class,'index'])->name('shop.index');
Route::get('/shop/{product_slug}',[ShopController::class,'product_details'])->name('shop.details');

Route::post('/cart/apply-coupon',[CartController::class,'apply_coupon_code'])->name('cart.apply_coupon');
Route::delete('/cart/remove-coupon',[CartController::class,'remove_coupon_code'])->name('cart.remove_coupon');

Route::post('/wishlist/add',[WishlistCotroller::class,'add_to_wishlist'])->name('wishlist.add');
Route::get('/wishlist',[WishlistCotroller::class,'index'])->name('wishlist.index');
Route::delete('/wishlist/remove-item/{rowId}',[WishlistCotroller::class,'remove_item'])->name('wishlist.remove_item');
Route::delete('/wishlist/clear',[WishlistCotroller::class,'empty_wishlist'])->name('wishlist.clear');
Route::post('/wishlist/move-to-cart/{rowId}',[WishlistCotroller::class,'move_to_cart'])->name('wishlist.move_to_cart');

Route::get('/cart',[CartController::class,'index'])->name('cart.index');
Route::post('/cart/add',[CartController::class,'add_to_cart'])->name('cart.add');

Route::put('/cart/increase-quantity/{rowId}',[CartController::class,'increase_quantity'])->name('cart.increase_quantity');
Route::put('/cart/decrease-quantity/{rowId}',[CartController::class,'decrease_quantity'])->name('cart.decrease_quantity');
Route::delete('/cart/remove-item/{rowId}',[CartController::class,'remove_item'])->name('cart.remove_item');
Route::delete('/cart/clear',[CartController::class,'clear_cart'])->name('cart.clear');

Route::get('/checkout',[CartController::class,'checkout'])->name('cart.checkout');
Route::post('/checkout/place-order',[CartController::class,'plance_an_order'])->name('cart.place_order');
Route::get('/order/confirmation',[CartController::class,'order_confirmation'])->name('cart.order_confirmation');

Route::middleware(['auth'],AuthAdmin::class)->group(function(){
    //brands + admin
    Route::get('/admin',[AdminControlller::class, 'index'] )->name('admin.index');
    Route::get('/admin/brands',[AdminControlller::class,'brands'])->name('admin.brands');
    Route::get('/admin/brand/add',[AdminControlller::class,'add_brand'])->name('admin.brand.add');
    Route::post('/admin/brand/store',[AdminControlller::class,'brands_store'])->name("admin.brand.store");
    Route::get('/admin/brand/edit/{id}',[AdminControlller::class,'brands_edit'])->name("admin.brand.edit");
    Route::put('/admin/brand/update',[AdminControlller::class,'brands_update'])->name('admin.brand.update');
    Route::delete('/admin/brand/{id}/delete',[AdminControlller::class,'brands_delete'])->name('admin.brand.delete');
    //categories
    Route::get('/admin/categories',[AdminControlller::class,"categories"])->name('admin.categories');
    Route::get('/admin/category/add',[AdminControlller::class,"add_category"])->name('admin.category.add');
    Route::post('/admin/category/store',[AdminControlller::class,'category_store'])->name('admin.category.store');
    Route::get('/admin/category/{id}/edit',[AdminControlller::class,'category_edit'])->name('admin.category.edit');
    Route::put('/admin/category/update',[AdminControlller::class,'category_update'])->name('admin.category.update');
    Route::delete('/admin/category/{id}/delete',[AdminControlller::class,'category_delete'])->name('admin.category.delete');
    //products
    Route::get('/admin/products',[AdminControlller::class,'products'])->name('admin.products');
    Route::get('/admin/product/add',[AdminControlller::class,'product_add'])->name('admin.product.add');
    Route::post('/admin/product/store',[AdminControlller::class,'product_store'])->name('admin.product.store');
    Route::get('/admin/product/{id}/edit',[AdminControlller::class,'product_edit'])->name('admin.product.edit');
    Route::put('/admin/product/update',[AdminControlller::class,'product_update'])->name('admin.product.update');
    Route::delete('/admin/product/{id}/delete',[AdminControlller::class,'product_delete'])->name('admin.product.delete');

    //$coupons
    Route::get('/admin/coupons',[AdminControlller::class,'coupons'])->name('admin.coupons');
    Route::get('/admin/coupons/add',[AdminControlller::class,'coupon_add'])->name('admin.coupon.add');
    Route::post('/admin/coupons/store',[AdminControlller::class,'coupon_store'])->name('admin.coupon.store');
    Route::get('/admin/coupons/{id}/edit',[AdminControlller::class,'coupon_edit'])->name('admin.coupon.edit');
    Route::put('/admin/coupons/update',[AdminControlller::class,'coupon_update'])->name('admin.coupon.update');
    Route::delete('/admin/coupons/{id}/delete',[AdminControlller::class,'coupon_delete'])->name('admin.coupon.delete');
    //orders
    Route::get('/admin/orders',[AdminControlller::class,'Order'])->name('admin.orders');
    Route::get('/admin/order/{order_id}/details',[AdminControlller::class,'order_details'])->name('admin.order.details');
    Route::put('/admin/order/update-status',[AdminControlller::class,'update_order_status'])->name('admin.order.update_status');

});

Route::middleware(['auth'])->group(function(){

    Route::get('/account-dashboard',[UserController::class, 'index'] )->name('user.index');
    Route::get('/account-orders',[UserController::class, 'orders'] )->name('user.orders');
    Route::get('/account-order/{order_id}/details',[UserController::class, 'order_details'] )->name('user.order.details');
    Route::put('/account-order/cancel',[UserController::class, 'cancell_order'] )->name('user.order.cancel');
});