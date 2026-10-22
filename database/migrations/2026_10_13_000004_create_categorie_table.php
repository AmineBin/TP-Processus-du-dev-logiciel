<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorie', function (Blueprint $table) {
            $table->integer('id', autoIncrement: true);
            $table->string('nom', 100);
            $table->integer('categorie_parent_id')->nullable();
            $table->text('description')->nullable();
            $table->index('categorie_parent_id', 'fk_categorie_parent');
            $table->foreign('categorie_parent_id', 'fk_categorie_parent')
                ->references('id')->on('categorie')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorie');
    }
};
