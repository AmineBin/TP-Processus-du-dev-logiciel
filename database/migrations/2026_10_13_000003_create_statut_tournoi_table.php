<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statut_tournoi', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->string('code', 30)->unique('uq_statut_tournoi_code');
            $table->string('libelle', 50);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statut_tournoi');
    }
};
