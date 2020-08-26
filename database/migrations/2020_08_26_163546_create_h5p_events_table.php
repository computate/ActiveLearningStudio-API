<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateH5pEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('h5p_events', function (Blueprint $table) {
            $table->id();
            $table->string('type', 63);
            $table->string('sub_type', 63);
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('content_id');
            $table->foreign('content_id')->references('id')->on('h5p_contents');
            $table->unsignedBigInteger('library_id');
            $table->foreign('library_id')->references('id')->on('h5p_libraries');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('h5p_events');
    }
}
