<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePreJobTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('yl_pre_job', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('yl_jobs')->unsigned();
            $table->foreignId('pre_job_id')->constrained('yl_jobs')->unsigned();
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
        Schema::dropIfExists('yl_pre_job');
    }
}
