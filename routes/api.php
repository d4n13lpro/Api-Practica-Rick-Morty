<?php

use App\Http\Controllers\Api\CharacterController;
use App\Http\Controllers\Api\GetCharacterByIdController;
use Illuminate\Support\Facades\Route;

Route::get('/characters', CharacterController::class);
Route::get('/characters/{id}', GetCharacterByIdController::class);
