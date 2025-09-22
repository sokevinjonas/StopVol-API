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
        Schema::create('declaration_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('declaration_id');
            
            // Type de document / image
            $table->enum('document_type', ['cnib', 'permis_conduire', 'passport', 'photo'])->default('photo');
            
            // Si c’est un document officiel, on peut préciser la face
            $table->enum('type', ['card_front', 'card_back'])->nullable(); 
            
            $table->string('path'); // chemin vers le fichier
            $table->timestamps();

            $table->foreign('declaration_id')->references('id')->on('declarations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('declaration_images');
    }
};
