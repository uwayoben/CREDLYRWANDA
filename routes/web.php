<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/contact', function () {
    return view('sales');
})->name('contact');

route::get('/team',function(){
    return view('team');

})->name('team');
