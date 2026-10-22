<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscription', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->integer('utilisateur_id');
            $table->integer('tournoi_id');
            $table->dateTime('date_inscription')->useCurrent();
            $table->integer('statut_id');
            $table->unique(['utilisateur_id', 'tournoi_id'], 'uq_inscription_utilisateur_tournoi');
            $table->index('tournoi_id', 'fk_inscription_tournoi');
            $table->index('statut_id', 'fk_inscription_statut');
            $table->foreign('utilisateur_id', 'fk_inscription_utilisateur')
                ->references('id')->on('utilisateur')->cascadeOnDelete();
            $table->foreign('tournoi_id', 'fk_inscription_tournoi')
                ->references('id')->on('tournoi')->cascadeOnDelete();
            $table->foreign('statut_id', 'fk_inscription_statut')
                ->references('id')->on('statut_inscription');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscription');
    }
};
