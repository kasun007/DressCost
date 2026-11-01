<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * How many pieces of each item to make, by size. Every size costs the same
 * per piece. Existing items become one Medium piece, so their totals stay
 * the same.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->unsignedInteger('quantity_small')->default(0)->after('profit_margin');
            $table->unsignedInteger('quantity_medium')->default(0)->after('quantity_small');
            $table->unsignedInteger('quantity_large')->default(0)->after('quantity_medium');
            $table->unsignedInteger('quantity_xl')->default(0)->after('quantity_large');
        });

        DB::table('items')->update(['quantity_medium' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['quantity_small', 'quantity_medium', 'quantity_large', 'quantity_xl']);
        });
    }
};
