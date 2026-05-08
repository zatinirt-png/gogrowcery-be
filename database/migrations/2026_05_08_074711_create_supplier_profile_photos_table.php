<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_profile_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_profile_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->string('foto_kebun_path');
            $table->string('foto_akses_jalan_path');
            $table->string('foto_pic_path');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();

            // Hanya boleh ada 1 set foto per supplier
            $table->unique('supplier_profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_profile_photos');
    }
};
