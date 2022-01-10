<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupUserJobStatusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('yl_group_user_job_status', function (Blueprint $table) {
            $table->id();
            $table->string('content');
            $table->tinyInteger('status');
            $table->foreignId('group_user_job_id')->constrained('yl_group_user_job')->unsigned();
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
        Schema::dropIfExists('yl_group_user_job_status');
    }
}
