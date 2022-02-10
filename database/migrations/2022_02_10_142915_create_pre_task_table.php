<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePreTaskTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('yl_pre_task', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('yl_tasks')->unsigned();
            $table->foreignId('pre_task_id')->constrained('yl_tasks')->unsigned();
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
        Schema::dropIfExists('yl_pre_task');
    }
}
