<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Items are dated by when they were saved (created_at) rather than by an
 * editable costing date.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['costed_on']);
            $table->dropColumn('costed_on');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations. The costing date comes back as the saved date.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->date('costed_on')->nullable()->after('type');
        });
    }
};
