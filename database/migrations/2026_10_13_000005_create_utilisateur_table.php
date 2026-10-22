<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utilisateur', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->string('nom_utilisateur', 50)->unique('uq_utilisateur_nom');
            $table->string('mot_de_passe_hash', 255);
            $table->date('date_naissance');
            $table->string('email', 100)->unique('uq_utilisateur_email');
            $table->string('numero_telephone', 20);
            $table->string('prenom', 50)->nullable();
            $table->string('nom', 50)->nullable();
            $table->dateTime('date_creation')->useCurrent();
            $table->boolean('est_actif')->default(true);
            $table->integer('role_id');
            $table->index('role_id', 'fk_utilisateur_role');
            $table->foreign('role_id', 'fk_utilisateur_role')->references('id')->on('role_utilisateur');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utilisateur');
    }
};
