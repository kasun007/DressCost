<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the fixed labour/overhead columns with free-form cost lines.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('item_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });

        foreach (['labour_cost' => 'Labour', 'overhead_cost' => 'Overhead'] as $column => $name) {
            DB::table('item_costs')->insertUsing(
                ['item_id', 'name', 'amount', 'created_at', 'updated_at'],
                DB::table('items')
                    ->where($column, '>', 0)
                    ->select('id', DB::raw('? as name'), $column, 'created_at', 'updated_at')
                    ->addBinding($name, 'select'),
            );
        }

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['labour_cost', 'overhead_cost']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->decimal('labour_cost', 10, 2)->default(0);
            $table->decimal('overhead_cost', 10, 2)->default(0);
        });

        Schema::dropIfExists('item_costs');
    }
};
