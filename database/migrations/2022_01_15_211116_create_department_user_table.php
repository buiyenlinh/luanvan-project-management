<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDepartmentUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('yl_department_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('yl_users')->unsigned();
            $table->foreignId('department_id')->constrained('yl_departments')->unsigned();
            $table->string('position');
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
        Schema::dropIfExists('yl_department_user');
    }
}
