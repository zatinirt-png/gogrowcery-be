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
        Schema::create('supplier_lands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_profile_id')->constrained()->cascadeOnDelete();
            $table->string('nama_pemilik');
            $table->string('no_hp', 20);
            $table->text('alamat_lahan');
            $table->string('desa', 100);
            $table->string('kelurahan', 100)->nullable();
            $table->string('kecamatan', 100);
            $table->string('kabupaten', 100);
            $table->string('provinsi', 100);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('akses_kendaraan', 100)->nullable();
            $table->text('catatan_akses')->nullable();
            $table->enum('kepemilikan', ['milik_sendiri', 'sewa', 'kerjasama', 'lainnya']);
            $table->string('kepemilikan_lainnya_keterangan')->nullable();
            $table->decimal('luas_lahan_m2', 10, 2);
            $table->enum('status_aktif', ['aktif', 'tidak_aktif', 'musiman'])->default('aktif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_lands');
    }
};