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
    Route::delete('delete-file/{id}', 'ProjectController@deleteFile');

    Route::post('take-project/{id}', 'ProjectController@takeProject')
      ->withoutMiddleware('check-role:1|2|3')->middleware('check-role:3');

    Route::post('finish-project/{id}', 'ProjectController@finishProject')
    ->withoutMiddleware('check-role:1|2|3')->middleware('check-role:3');
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
      Route::post('update', 'TaskController@update');
      Route::delete('delete/{id_task}', 'TaskController@delete');
      Route::delete('delete-file/{id_task}', 'TaskController@deleteFile');
      Route::post('take-task/{task_id}', 'TaskController@takeTask')->withoutMiddleware('check-role:1|2|3');
      Route::post('finish-task/{task_id}', 'TaskController@finishTask')->withoutMiddleware('check-role:1|2|3');
      Route::post('approval-finish-task/{task_id}', 'TaskController@approvalFinishTask')->withoutMiddleware('check-role:1|2|3')->middleware('check-role:3');
      Route::post('not-approval-finish-task/{task_id}', 'TaskController@notApprovalFinishTask')->withoutMiddleware('check-role:1|2|3')->middleware('check-role:3');
    });

    Route::prefix('task/{task_id}')->group(function() {
      Route::get('info', 'JobController@getInfo');
      Route::post('search-user-member', 'JobController@searchUserMember');
      Route::post('search-job', 'JobController@searchJob');
      Route::post('add', 'JobController@add');
      Route::get('list', 'JobController@list');
      Route::post('update/{job_id}', 'JobController@update');
      Route::delete('delete-file/{job_id}', 'JobController@deleteFile');
      Route::delete('delete/{job_id}', 'JobController@delete');
      Route::post('take-job/{job_id}', 'JobController@takeJob');
      Route::post('refuse-job/{job_id}', 'JobController@refuseJob');
      Route::post('finish-job/{job_id}', 'JobController@finishJob');
      Route::post('approval-job/{job_id}', 'JobController@approvalJob');
      Route::post('not-approval-job/{job_id}', 'JobController@notApprovalJob');
      Route::post('not-approval-refuse-job/{job_id}', 'JobController@notApprovalRefuseJob');
    });
  });

  Route::prefix('label')->middleware('check-role:1|2')->group(function() {
    Route::post('search', 'LabelController@searchLabel');
  });

  Route::prefix('/role')->group(function() {
    Route::get('list', 'UserController@getRoleList');
  });
});
