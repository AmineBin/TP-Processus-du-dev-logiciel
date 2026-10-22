<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_utilisateur', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->string('code', 30)->unique('uq_role_code');
            $table->string('libelle', 50);
            $table->string('description', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_utilisateur');
    }
};
