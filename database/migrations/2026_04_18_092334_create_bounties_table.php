<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bounties', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('client_name');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('deadline_at');
            $table->timestamp('original_deadline_at');
            $table->timestamp('extended_deadline_at')->nullable();
            $table->enum('status', ['draft', 'published', 'closed', 'cancelled'])
                  ->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('deadline_at');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bounties');
    }
};
