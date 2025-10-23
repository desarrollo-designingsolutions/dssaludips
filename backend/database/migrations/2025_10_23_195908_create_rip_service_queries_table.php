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
        Schema::create('rip_service_queries', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('rip_invoice_user_id')->constrained();
            $table->string('codPrestador')->nullable();
            $table->string('fechaInicioAtencion')->nullable();
            $table->string('numAutorizacion')->nullable();
            $table->string('codConsulta')->nullable();
            $table->string('modalidadGrupoServicioTecSal')->nullable();
            $table->string('grupoServicios')->nullable();
            $table->string('codServicio')->nullable();
            $table->string('finalidadTecnologiaSalud')->nullable();
            $table->string('causaMotivoAtencion')->nullable();
            $table->string('codDiagnosticoPrincipal')->nullable();
            $table->string('codDiagnosticoRelacionado1')->nullable();
            $table->string('codDiagnosticoRelacionado2')->nullable();
            $table->string('codDiagnosticoRelacionado3')->nullable();
            $table->string('tipoDiagnosticoPrincipal')->nullable();
            $table->string('tipoDocumentoIdentificacion')->nullable();
            $table->string('numDocumentoIdentificacion')->nullable();
            $table->string('vrServicio')->nullable();
            $table->string('conceptoRecaudo')->nullable();
            $table->string('valorPagoModerador')->nullable();
            $table->string('numFEVPagoModerador')->nullable();
            $table->string('consecutivo')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rip_service_queries');
    }
};
