<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('supplier_payout_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_profile_id')->constrained()->cascadeOnDelete();
            $table->enum('payout_method', ['transfer', 'ewallet']);
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_branch', 100)->nullable();
            $table->string('ewallet_name', 100)->nullable();
            $table->string('ewallet_account_number', 50)->nullable();
            $table->string('ewallet_account_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_payout_accounts');
    }
};
