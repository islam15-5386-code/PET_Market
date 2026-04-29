<?php

use Illuminate\Support\Facades\Route;

// Pet Marketplace is a pure API — no web routes needed.
Route::get('/', fn () => response()->json(['service' => 'Pet Marketplace API']));
