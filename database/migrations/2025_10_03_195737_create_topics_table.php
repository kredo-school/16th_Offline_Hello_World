<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('topics', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->unsignedBigInteger('course_id');
        $table->string('title'); 
        $table->timestamps();
        $table->boolean('status')->default(1);
            $table->index('status');

        $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
