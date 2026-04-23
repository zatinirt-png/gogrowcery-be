<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_profiles', function (Blueprint $table) {
            $table->string('ktp_document_path')->nullable()->after('no_ktp');
            $table->string('npwp_document_path')->nullable()->after('kontak_darurat');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_profiles', function (Blueprint $table) {
            $table->dropColumn(['ktp_document_path', 'npwp_document_path']);
        });
    }
};
