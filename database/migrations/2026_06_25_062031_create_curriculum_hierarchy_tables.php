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
        // 1. Create learning_elements table
        Schema::create('learning_elements', function (Blueprint $table) {
            $table->id('id_element');
            $table->unsignedBigInteger('id_guru');
            $table->unsignedBigInteger('id_mapel');
            $table->string('nama_elemen');
            $table->text('deskripsi_cp');
            $table->timestamps();

            $table->foreign('id_guru')->references('id_guru')->on('teachers')->onDelete('cascade');
            $table->foreign('id_mapel')->references('id_mapel')->on('subjects')->onDelete('cascade');
        });

        // 2. Add id_element to learning_objectives table
        Schema::table('learning_objectives', function (Blueprint $table) {
            $table->unsignedBigInteger('id_element')->nullable()->after('id_mapel');
            $table->foreign('id_element')->references('id_element')->on('learning_elements')->onDelete('cascade');
        });

        // 3. Create learning_topics table
        Schema::create('learning_topics', function (Blueprint $table) {
            $table->id('id_topic');
            $table->unsignedBigInteger('id_tp');
            $table->string('nama_topik');
            $table->timestamps();

            $table->foreign('id_tp')->references('id_tp')->on('learning_objectives')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_topics');

        Schema::table('learning_objectives', function (Blueprint $table) {
            $table->dropForeign(['id_element']);
            $table->dropColumn('id_element');
        });

        Schema::dropIfExists('learning_elements');
    }
};
