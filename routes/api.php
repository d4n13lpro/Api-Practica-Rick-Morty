<?php

use App\Http\Controllers\Api\CharacterController;
use Illuminate\Support\Facades\Route;

Route::get('/characters', CharacterController::class);
