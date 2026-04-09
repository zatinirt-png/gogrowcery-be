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
        $table->string('nama_lengkap');
        $table->string('no_ktp', 20)->unique();
        $table->string('tempat_lahir', 100);
        $table->date('tanggal_lahir');
        $table->enum('jenis_kelamin', ['laki_laki', 'perempuan']);
        $table->string('pendidikan', 100)->nullable();
        $table->enum('status_perkawinan', ['belum_kawin', 'kawin', 'janda_duda']);
        $table->string('no_hp', 20)->unique();
        $table->string('email')->nullable();
        $table->text('alamat_domisili');
        $table->string('desa', 100);
        $table->string('kecamatan', 100);
        $table->string('kabupaten', 100);
        $table->string('kontak_darurat')->nullable();
        $table->json('bahasa_komunikasi')->nullable();
        $table->enum('approval_status', ['pending', 'approved', 'rejected'])
              ->default('pending');
        $table->enum('survey_status', ['belum_survey', 'dijadwalkan', 'sudah_survey'])
              ->default('belum_survey');
        $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamp('approved_at')->nullable();
        $table->text('rejection_reason')->nullable();
        $table->text('survey_notes')->nullable();
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
