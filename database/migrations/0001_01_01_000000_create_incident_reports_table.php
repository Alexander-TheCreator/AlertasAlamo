<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('incident_reports', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 25)->unique(); // Ej: ALM-2026-0001
            $table->string('category', 50); // bache, drenaje, alumbrado, agua, basura, accidente
            $table->string('department', 80); // Obras Públicas, CAEV / Drenaje, Alumbrado, etc.
            $table->enum('priority', ['baja', 'media', 'alta', 'critica'])->default('media');

            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->string('colonia', 100);
            $table->string('address_reference', 255)->nullable();

            // Coordenadas compatibles con cualquier MySQL de Hostinger
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // Datos del ciudadano (opcionales para reportes rápidos)
            $table->string('citizen_name', 100)->default('Ciudadano Anónimo');
            $table->string('citizen_phone', 25)->nullable();

            // Evidencias Fotográficas (Antes y Después)
            $table->string('evidence_initial')->nullable(); // Foto del ciudadano
            $table->string('evidence_resolution')->nullable(); // Foto de la cuadrilla municipal
            $table->text('resolution_notes')->nullable(); // Comentario de cierre del municipio

            // Estado y Votación Comunitaria ("+1 Me afecta")
            $table->enum('status', ['pendiente', 'en_proceso', 'resuelto', 'rechazado'])->default('pendiente');
            $table->unsignedInteger('upvotes')->default(1);

            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_reports');
    }
};