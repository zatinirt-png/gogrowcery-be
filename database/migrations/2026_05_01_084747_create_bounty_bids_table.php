<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bounty_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bounty_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_profile_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['draft', 'submitted', 'revised', 'withdrawn'])
                  ->default('submitted');
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('revised_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();

            // Satu supplier hanya boleh punya 1 bid per bounty
            $table->unique(['bounty_id', 'supplier_profile_id']);
            $table->index('bounty_id');
            $table->index('supplier_profile_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bounty_bids');
    }
};
