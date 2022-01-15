<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDepartmentUserJobTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('yl_department_user_job', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_user_id')->constrained('yl_department_user')->unsigned();
            $table->foreignId('job_id')->constrained('yl_jobs')->unsigned();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('yl_department_user_job');
    }
}
