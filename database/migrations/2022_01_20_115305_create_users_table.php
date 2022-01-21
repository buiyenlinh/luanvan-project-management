<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('yl_users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('fullname')->default('');
            $table->string('email')->unique();
            $table->string('token')->default('');
            $table->string('phone')->unique();
            $table->string('avatar')->default('');
            $table->integer('birthday')->default(0);
            $table->enum('gender', ['N', 'F', 'M'])->default('N');
            $table->boolean('active')->default(1);
            $table->foreignId('role_id')->constrained('yl_roles')->unsigned();
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
        Schema::dropIfExists('yl_users');
    }
}
