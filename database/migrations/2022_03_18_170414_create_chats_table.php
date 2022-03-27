<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Model\User;

class CreateChatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('yl_chats', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);
            $table->foreignId('sender')->constrained(User::getTableName())->unsigned();
            $table->enum('type', ['text','image']);
            $table->string('content');
            $table->boolean('seen')->default(false);
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
        Schema::dropIfExists('yl_chats');
    }
}
