<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('events', 'min_price')) {
            Schema::table('events', function (Blueprint $table) {
                $table->index(
                    ['status', 'min_price', 'created_time'],
                    'events_status_min_price_created_time_index',
                );
            });
        }

        Schema::table('events', function (Blueprint $table) {
            $table->index(
                ['status', 'type', 'created_time', 'id'],
                'events_status_type_created_time_id_index',
            );
        });

        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA optimize');
        }
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_status_min_price_created_time_index');
            $table->dropIndex('events_status_type_created_time_id_index');
        });
    }
};
