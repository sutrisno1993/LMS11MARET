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
        Schema::create('learning_objective_class', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_tp');
            $table->unsignedBigInteger('id_kelas');
            $table->timestamps();

            $table->foreign('id_tp')->references('id_tp')->on('learning_objectives')->onDelete('cascade');
            $table->foreign('id_kelas')->references('id_kelas')->on('classes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_objective_class');
    }
};
