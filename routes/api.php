<?php

use App\Http\Controllers\ResumeController;

Route::post('/resume/upload', [ResumeController::class,'upload']);
Route::post('/resume/score',  [ResumeController::class,'score']);
Route::get('/vacancies/{id}/rankings', [ResumeController::class,'rankings']);
Route::get('/applications/{id}/explanation', [ResumeController::class,'explanation']);
