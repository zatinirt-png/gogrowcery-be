<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bounty_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bounty_id')->constrained()->cascadeOnDelete();
            $table->string('item_name');
            $table->decimal('target_quantity', 10, 2);
            $table->string('unit', 50);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('bounty_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bounty_items');
    }
};
