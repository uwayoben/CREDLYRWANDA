<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoanPrintController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/contact', function () {
    return view('sales');
})->name('contact');

route::get('/team',function(){
    return view('team');

})->name('team');



Route::get('/loans/{loan}/print', [LoanPrintController::class, 'show'])
    ->name('loans.print')
    ->middleware(['auth']);