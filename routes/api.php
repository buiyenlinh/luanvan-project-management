<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/config', 'AppController@getConfig');

Route::middleware('is-token')->group(function() {
  Route::post('/login', 'AppController@login')->withoutMiddleware('is-token');
  Route::prefix("/user")->group(function() {
    Route::get('list', 'UserController@getUserList');
  });
});
