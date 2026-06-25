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
        Schema::table('student_grades', function (Blueprint $table) {
            $table->unsignedBigInteger('id_tp')->nullable()->change();
            $table->unsignedBigInteger('id_topic')->nullable()->after('id_tp');
            $table->foreign('id_topic')->references('id_topic')->on('learning_topics')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_grades', function (Blueprint $table) {
            $table->dropForeign(['id_topic']);
            $table->dropColumn('id_topic');
            // We do not force nullable(false) back in change to avoid sql errors if nulls exist
        });
    }
};
