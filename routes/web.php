<?php

use Illuminate\Support\Facades\Route;

// Catch all routes - React SPA
Route::view('/{any?}', 'app')->where('any', '.*');
