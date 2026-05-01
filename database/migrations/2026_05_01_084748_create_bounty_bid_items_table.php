<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bounty_bid_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bounty_bid_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bounty_item_id')->constrained()->cascadeOnDelete();
            $table->enum('grade', ['A', 'B', 'C']);
            $table->decimal('estimasi_harga', 12, 2);
            $table->decimal('estimasi_kuantitas', 10, 2);
            $table->text('catatan')->nullable();
            $table->timestamps();

            // Satu bid hanya boleh punya 1 item per bounty_item
            $table->unique(['bounty_bid_id', 'bounty_item_id']);
            $table->index('bounty_bid_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bounty_bid_items');
    }
};
