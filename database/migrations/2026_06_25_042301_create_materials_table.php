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
        Schema::create('materials', function (Blueprint $table) {
            $table->id('id_materi');
            $table->unsignedBigInteger('id_guru');
            $table->unsignedBigInteger('id_mapel');
            $table->unsignedBigInteger('id_kelas');
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size');
            $table->timestamps();

            $table->foreign('id_guru')->references('id_guru')->on('teachers')->onDelete('cascade');
            $table->foreign('id_mapel')->references('id_mapel')->on('subjects')->onDelete('cascade');
            $table->foreign('id_kelas')->references('id_kelas')->on('classes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};