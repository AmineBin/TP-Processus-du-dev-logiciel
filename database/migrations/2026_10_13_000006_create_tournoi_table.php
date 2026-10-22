<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournoi', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->string('nom', 100);
            $table->text('description')->nullable();
            $table->text('regles')->nullable();
            $table->dateTime('date_debut');
            $table->dateTime('date_fin')->nullable();
            $table->dateTime('date_limite_inscription')->nullable();
            $table->integer('nombre_max_participants')->nullable();
            $table->integer('organisateur_id');
            $table->integer('categorie_id');
            $table->dateTime('date_creation')->useCurrent();
            $table->dateTime('date_modification')->useCurrent()->useCurrentOnUpdate();
            $table->integer('statut_id');
            $table->index('organisateur_id', 'fk_tournoi_organisateur');
            $table->index('categorie_id', 'fk_tournoi_categorie');
            $table->index('statut_id', 'fk_tournoi_statut');
            $table->foreign('organisateur_id', 'fk_tournoi_organisateur')->references('id')->on('utilisateur');
            $table->foreign('categorie_id', 'fk_tournoi_categorie')->references('id')->on('categorie');
            $table->foreign('statut_id', 'fk_tournoi_statut')->references('id')->on('statut_tournoi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournoi');
    }
};
