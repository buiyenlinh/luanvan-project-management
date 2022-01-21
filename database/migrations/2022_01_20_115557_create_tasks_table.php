<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('yl_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('describe')->default('');
            $table->text('result')->default('');
            $table->integer('start_time')->default(0);
            $table->integer('end_time')->default(0);
            $table->smallinteger('delay_time')->default(0); // Đơn vị: Ngày
            $table->foreignId('label_id')->constrained('yl_labels')->unsigned();
            $table->foreignId('project_id')->constrained('yl_projects')->unsigned();
            $table->integer('pre_task_id');
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
        Schema::dropIfExists('yl_tasks');
    }
}