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
        Schema::create('student_disciplines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_siswa');
            $table->string('tipe_tindakan'); // 'PEMBINAAN_PRIBADI', 'PEMBINAAN_PERSONAL', 'PEMANGGILAN_ORTU', 'SP_1', 'SP_2', 'SP_3', 'PERJANJIAN_TIDAK_NAIK'
            $table->date('tanggal_tindakan');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->foreign('id_siswa')->references('id_siswa')->on('students')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_disciplines');
    }
};
