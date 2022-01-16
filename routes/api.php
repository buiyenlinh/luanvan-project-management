<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/config', 'AppController@getConfig');

Route::middleware(['is-token', 'check-role:1|2'])->group(function() {
  Route::post('/login', 'AppController@login')->withoutMiddleware(['is-token', 'check-role']);
  Route::get('/logout', 'AppController@logout')->withoutMiddleware('check-role:1|2');

  Route::post('/profile', 'UserController@updateProfile')->withoutMiddleware('check-role:1|2');
  Route::delete('/profile/delete-avatar', 'UserController@deleteAvatar')->withoutMiddleware('check-role:1|2');
  Route::prefix("/user")->group(function() {
    Route::get('list', 'UserController@getUserList')->withoutMiddleware('check-role:1|2');
    Route::post('add', 'UserController@addUser');
    Route::post('update', 'UserController@updateUser');
    Route::delete('delete/{id}', 'UserController@deleteUser');
    Route::post('search', 'UserController@search');
  });

  Route::prefix('/role')->group(function() {
    Route::get('list', 'UserController@getRoleList');
  });
});
