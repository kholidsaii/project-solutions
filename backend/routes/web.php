<?php

use Illuminate\Support\Facades\Route;

// Redirect halaman depan langsung ke login
Route::get('/', function () {
    return redirect('/login');
});

// Jika kamu punya rute lain untuk frontend, tambahkan di sini