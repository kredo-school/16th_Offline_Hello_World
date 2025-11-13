<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->decimal('price', 8, 2)->default(5000.00);
            $table->text('description')->nullable(); 
            $table->string('image_url')->nullable();
            $table->boolean('status')->default(1);
            $table->string('language')->default('en'); 
            $table->enum('level', ['basic', 'advance', 'expert'])->default('basic'); 
            $table->longText('image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
