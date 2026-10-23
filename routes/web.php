<?php

use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::redirect('/privacy-policy', '/privacy-policy-2', 301);
Route::redirect('/privacy-policy/', '/privacy-policy-2/', 301);
Route::redirect('/free-audit', '/contact-us', 301);
Route::redirect('/free-audit/', '/contact-us/', 301);
Route::redirect('/get-a-free-audit', '/contact-us', 301);
Route::redirect('/get-a-free-audit/', '/contact-us/', 301);
Route::redirect('/our-services', '/services', 301);
Route::redirect('/our-services/', '/services/', 301);

Route::get('/{path?}', [SiteController::class, 'show'])->where('path', '.*');
