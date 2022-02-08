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
    Route::post('search-user', 'UserController@searchUser');
    Route::post('search-user-not-deparment', 'UserController@searchUserNotDepartment');
  });

  Route::prefix('project')->middleware('check-role:1|2|3')->group(function() {
    Route::get('list', 'ProjectController@listProject')->withoutMiddleware('check-role:1|2|3');
    Route::get('info/{id}', 'ProjectController@getProjectById')->withoutMiddleware('check-role:1|2|3');
    Route::post('add', 'ProjectController@addProject');
    Route::post('update', 'ProjectController@updateProject');
    Route::delete('delete/{id}', 'ProjectController@deleteProject');
  });

  Route::prefix('department')->middleware('check-role:1|2')->group(function() {
    Route::get('list', 'DepartmentController@getList')->withoutMiddleware('check-role:1|2');
    Route::post('add', 'DepartmentController@create');
    Route::post('update', 'DepartmentController@update');
    Route::delete('delete/{id}', 'DepartmentController@delete');
    Route::post('search', 'DepartmentController@searchDepartment');
  });

  Route::prefix('project/{id}')->group(function(){
    Route::prefix('task')->middleware('check-role:1|2|3')->group(function() {
      Route::get('list', 'TaskController@taskInProject')->withoutMiddleware('check-role:1|2|3');
      Route::post('search', 'TaskController@searchTaskName');
      Route::post('add', 'TaskController@add');
    });
  });

  Route::prefix('label')->middleware('check-role:1|2')->group(function() {
    Route::post('search', 'LabelController@searchLabel');
  });

  Route::prefix('/role')->group(function() {
    Route::get('list', 'UserController@getRoleList');
  });
});
