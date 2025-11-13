<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) 旧値を新値にマッピング（存在する場合のみ）
        DB::table('reports')
            ->where('status', 'leave in the middle')
            ->update(['status' => 'other']);

        // 2) ENUM定義を差し替え（MySQL）
        DB::statement("
            ALTER TABLE `reports`
            MODIFY `status` ENUM('attended', 'absent', 'canceled by teacher', 'other')
            NOT NULL DEFAULT 'attended'
        ");
    }

    public function down(): void
    {
        // ダウングレード時のために、まず 'others' を 旧値に戻せるようにしておく
        DB::table('reports')
            ->where('status', 'other')
            ->update(['status' => 'leave in the middle']);

        // 旧ENUMへ戻す
        DB::statement("
            ALTER TABLE `reports`
            MODIFY `status` ENUM('attended', 'absent', 'leave in the middle')
            NOT NULL DEFAULT 'attended'
        ");
    }
};