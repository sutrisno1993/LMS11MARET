<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_kbm_session');
            $table->foreign('id_kbm_session')->references('id')->on('kbm_sessions')->onDelete('cascade');
            
            $table->unsignedBigInteger('id_siswa');
            $table->foreign('id_siswa')->references('id_siswa')->on('students')->onDelete('cascade');
            
            $table->enum('status', ['HADIR', 'SAKIT', 'IZIN', 'ALPA'])->default('HADIR');
            $table->timestamp('waktu_presensi')->nullable();
            $table->enum('metode', ['SCAN_QR', 'MANUAL_GURU', 'SYSTEM'])->default('SYSTEM');
            $table->timestamps();

            // Hindari duplikasi presensi siswa pada sesi yang sama
            $table->unique(['id_kbm_session', 'id_siswa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_attendances');
    }
};
