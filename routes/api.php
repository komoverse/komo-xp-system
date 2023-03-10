<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExperienceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/experience', [ExperienceController::class, 'api_experience']);
Route::get('/experience', [ExperienceController::class, 'api_experience']);

// Archived Routes
// Route::get('/unified-daily-experience', [App\Http\Controllers\UnifiedDailyExperienceController::class, 'api_unified_daily_experience']);
// Route::post('/unified-daily-experience', [App\Http\Controllers\UnifiedDailyExperienceController::class, 'api_unified_daily_experience']);
//
// Route::get('/daily-experience', [App\Http\Controllers\DailyExperienceController::class, 'api_daily_experience']);
// Route::post('/daily-experience', [App\Http\Controllers\DailyExperienceController::class, 'api_daily_experience']);
//
// Route::get('/mmr-experience', [App\Http\Controllers\MmrExperienceController::class, 'api_mmr_experience']);
// Route::post('/mmr-experience', [App\Http\Controllers\MmrExperienceController::class, 'api_mmr_experience']);
//
// Route::get('/compendium-experience', [App\Http\Controllers\CompendiumExperienceController::class, 'api_compendium_experience']);
// Route::post('/compendium-experience', [App\Http\Controllers\CompendiumExperienceController::class, 'api_compendium_experience']);
