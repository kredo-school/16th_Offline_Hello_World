<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    // public function up(): void
    // {
    //     Schema::table('users', function (Blueprint $table) {
    //         $table->string('status')->default('inactive');
    //     });
    // }
    // public function down(): void
    // {
    //     Schema::table('users', function (Blueprint $table) {
    //         $table->dropColumn('status');
    //     });
    // }

     public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('status', ['active','inactive'])
                  ->default('active')
                  ->after('email_verified_at');
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
