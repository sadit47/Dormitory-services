<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'ok' => true,
        'message' => 'Dorm Service API is running',
    ]);
});

Route::get('/login', function () {
    return redirect('https://dormitory-services.vercel.app/admin/login');
});