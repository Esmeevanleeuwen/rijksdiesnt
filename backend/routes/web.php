<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'name' => 'Rijksdienst API',
    'version' => 'v1',
    'data_mode' => 'demo',
    'organizations' => '/api/v1/organizations',
]));
