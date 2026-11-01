<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The date an item was costed, used for trends and date filters in reports.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->date('costed_on')->nullable()->after('type');
        });

        DB::table('items')->whereNull('costed_on')->orderBy('id')->each(function (object $item) {
            DB::table('items')->where('id', $item->id)->update([
                'costed_on' => substr((string) ($item->created_at ?? now()), 0, 10),
            ]);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->date('costed_on')->nullable(false)->change();
            $table->index('costed_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['costed_on']);
            $table->dropColumn('costed_on');
        });
    }
};
