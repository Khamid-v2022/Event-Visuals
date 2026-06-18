<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->index('type', 'events_type_index');
            $table->index(['status', 'type', 'created_time'], 'events_status_type_created_time_index');
        });

        // SQLite cannot add STORED generated columns to existing tables; price sort uses json_extract().
        if (Schema::getConnection()->getDriverName() === 'mysql' && ! Schema::hasColumn('events', 'min_price')) {
            DB::statement("ALTER TABLE events ADD COLUMN min_price DECIMAL(10, 2) GENERATED ALWAYS AS (CAST(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.pricing.min_price')) AS DECIMAL(10, 2))) STORED");

            Schema::table('events', function (Blueprint $table) {
                $table->index('min_price', 'events_min_price_index');
            });
        }
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_type_index');
            $table->dropIndex('events_status_type_created_time_index');
        });

        if (Schema::hasColumn('events', 'min_price')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropIndex('events_min_price_index');
                $table->dropColumn('min_price');
            });
        }
    }
};
