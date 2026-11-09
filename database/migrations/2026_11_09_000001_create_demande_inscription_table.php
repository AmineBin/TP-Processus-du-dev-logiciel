<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** File d’attente organisateur, basée sur le SQL proposé par la branche database-schema. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demande_inscription', function (Blueprint $table): void {
            $table->integer('id', autoIncrement: true);
            $table->string('nom_utilisateur', 50);
            $table->string('email', 100);
            $table->string('mot_de_passe_hash', 255);
            $table->string('prenom', 50)->nullable();
            $table->string('nom', 50)->nullable();
            $table->dateTime('date_creation')->useCurrent();
            $table->date('date_naissance')->nullable();
            $table->string('numero_telephone', 20)->nullable();
            $table->integer('role_id');
            $table->enum('statut', ['EN_ATTENTE', 'APPROUVEE', 'REFUSEE'])->default('EN_ATTENTE');
            $table->dateTime('date_traitement')->nullable();
            $table->integer('traite_par')->nullable();
            $table->string('motif_refus', 500)->nullable();
            $table->integer('utilisateur_cree_id')->nullable();

            // Une seule demande en attente par identité, même en concurrence.
            // Les valeurs deviennent NULL après traitement : un refus autorise une nouvelle demande.
            $table->string('email_en_attente', 100)->nullable()
                ->virtualAs("CASE WHEN statut = 'EN_ATTENTE' THEN LOWER(email) ELSE NULL END")
                ->unique('uq_demande_email_attente');
            $table->string('pseudo_en_attente', 50)->nullable()
                ->virtualAs("CASE WHEN statut = 'EN_ATTENTE' THEN LOWER(nom_utilisateur) ELSE NULL END")
                ->unique('uq_demande_pseudo_attente');

            $table->index(['statut', 'date_creation', 'id'], 'idx_demande_file_attente');
            $table->foreign('role_id', 'fk_demande_role')->references('id')->on('role_utilisateur');
            $table->foreign('traite_par', 'fk_demande_traite_par')->references('id')->on('utilisateur')->nullOnDelete();
            $table->foreign('utilisateur_cree_id', 'fk_demande_utilisateur_cree')->references('id')->on('utilisateur')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demande_inscription');
    }
};
