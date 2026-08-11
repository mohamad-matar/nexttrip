<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            if (! Schema::hasColumn('trips', 'days')) {
                $table->integer('days')->nullable()->after('start_date');
            }
            if (! Schema::hasColumn('trips', 'end_date')) {
                $table->date('end_date')->nullable()->after('days');
            }
            if (! Schema::hasColumn('trips', 'total_cost')) {
                $table->decimal('total_cost', 12, 2)->nullable()->after('total_estimated_cost');
            }
            if (! Schema::hasColumn('trips', 'source')) {
                $table->string('source')->default('manual')->after('preferred_activity_level');
            }
            if (! Schema::hasColumn('trips', 'ai_payload')) {
                $table->json('ai_payload')->nullable()->after('source');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $columns = array_filter(['days', 'end_date', 'total_cost', 'source', 'ai_payload'], fn ($column) => Schema::hasColumn('trips', $column));
            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
