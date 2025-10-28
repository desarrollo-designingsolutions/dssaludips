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
        Schema::create('rip_service_procedures', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('rip_invoice_user_id')->constrained();
            $table->string('codPrestador')->nullable();
            $table->string('fechaInicioAtencion')->nullable();
            $table->string('idMIPRES')->nullable();
            $table->string('numAutorizacion')->nullable();
            $table->string('codProcedimiento')->nullable();
            $table->string('viaIngresoServicioSalud')->nullable();
            $table->string('modalidadGrupoServicioTecSal')->nullable();
            $table->string('grupoServicios')->nullable();
            $table->string('codServicio')->nullable();
            $table->string('finalidadTecnologiaSalud')->nullable();
            $table->string('tipoDocumentoIdentificacion')->nullable();
            $table->string('numDocumentoIdentificacion')->nullable();
            $table->string('codDiagnosticoPrincipal')->nullable();
            $table->string('codDiagnosticoRelacionado')->nullable();
            $table->string('codComplicacion')->nullable();
            $table->float('vrServicio')->nullable();
            $table->string('conceptoRecaudo')->nullable();
            $table->float('valorPagoModerador')->nullable();
            $table->string('numFEVPagoModerador')->nullable();
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
        Schema::dropIfExists('rip_service_procedures');
    }
};
