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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->decimal('labour_cost', 10, 2)->default(0);
            $table->decimal('overhead_cost', 10, 2)->default(0);
            $table->decimal('profit_margin', 5, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('item_material', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 10, 3);
            $table->timestamps();

            $table->unique(['item_id', 'material_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_material');
        Schema::dropIfExists('items');
    }
};
