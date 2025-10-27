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
        Schema::create('rip_service_newly_borns', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('rip_invoice_user_id')->constrained();
            $table->string('codPrestador')->nullable();
            $table->string('tipoDocumentoIdentificacion')->nullable();
            $table->string('numDocumentoIdentificacion')->nullable();
            $table->string('fechaNacimiento')->nullable();
            $table->string('edadGestacional')->nullable();
            $table->string('numConsultasCPrenatal')->nullable();
            $table->string('codSexoBiologico')->nullable();
            $table->string('peso')->nullable();
            $table->string('codDiagnosticoPrincipal')->nullable();
            $table->string('condicionDestinoUsuarioEgreso')->nullable();
            $table->string('codDiagnosticoCausaMuerte')->nullable();
            $table->string('fechaEgreso')->nullable();
            $table->integer('consecutivo')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rip_service_newly_borns');
    }
};
