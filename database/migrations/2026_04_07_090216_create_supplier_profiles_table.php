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
    Schema::create('supplier_profiles', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('store_name');
        $table->string('phone')->nullable();
        $table->string('npwp')->nullable();
        $table->string('address')->nullable();
        $table->enum('approval_status', ['pending', 'approved', 'rejected'])
              ->default('pending');
        $table->text('rejection_reason')->nullable();
        $table->boolean('registered_by_admin')->default(false);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_profiles');
    }
};
