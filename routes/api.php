<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/config', 'AppController@getConfig');

Route::middleware('is-token')->group(function() {
  Route::post('/login', 'AppController@login')->withoutMiddleware('is-token');
  Route::get('/logout', 'AppController@logout');
  Route::post('/profile', 'UserController@updateProfile');
  Route::delete('/profile/delete-avatar', 'UserController@deleteAvatar');

  Route::prefix("/user")->middleware('check-role:1|2')->group(function() {
    Route::get('list', 'UserController@getUserList')->withoutMiddleware('check-role:1|2');
    Route::post('add', 'UserController@addUser');
    Route::post('update', 'UserController@updateUser');
    Route::delete('delete/{id}', 'UserController@deleteUser');
    Route::post('search-manager', 'UserController@searchManager');
  });

  Route::prefix('project')->middleware('check-role:1|2|3')->group(function() {
    Route::get('list', 'ProjectController@listProject')->withoutMiddleware('check-role:1|2|3');
    Route::post('add', 'ProjectController@addProject');
    Route::post('update', 'ProjectController@updateProject');
    Route::delete('delete/{id}', 'ProjectController@deleteProject');
  });

  Route::prefix('/role')->group(function() {
    Route::get('list', 'UserController@getRoleList');
  });
});
