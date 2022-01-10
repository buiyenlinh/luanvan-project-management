<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('yl_projects', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('describe')->default('');
            $table->integer('start_time')->default(0);;
            $table->integer('end_time')->default(0);;
            $table->integer('smallinteger')->default(0);; // Đơn vị: Ngày
            $table->boolean('active')->default(1);
            $table->foreignId('manager')->constrained('yl_users')->unsigned();
            $table->foreignId('created_by')->constrained('yl_users')->unsigned(); // Người tạo dự án
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
        Schema::dropIfExists('yl_projects');
    }
}
