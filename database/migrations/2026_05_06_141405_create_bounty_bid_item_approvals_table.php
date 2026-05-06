<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bounty_bid_item_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bounty_bid_item_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->foreignId('approved_by')
                  ->constrained('users');
            $table->enum('status', ['approved', 'rejected'])
                  ->default('approved');
            $table->string('proof_photo_path')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamp('approved_at');
            $table->timestamps();

            $table->unique('bounty_bid_item_id'); // 1 approval per item
            $table->index('approved_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bounty_bid_item_approvals');
    }
};
