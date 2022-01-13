<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/config', 'AppController@getConfig');

Route::middleware(['is-token', 'check-role:1|2'])->group(function() {
  Route::post('/login', 'AppController@login')->withoutMiddleware(['is-token', 'check-role']);
  Route::prefix("/user")->group(function() {
    Route::get('list', 'UserController@getUserList')->withoutMiddleware('check-role:1|2');
    Route::post('add', 'UserController@addUser');
    Route::post('update', 'UserController@updateUser');
  });

  Route::prefix('/role')->group(function() {
    Route::get('list', 'UserController@getRoleList');
  });
});
