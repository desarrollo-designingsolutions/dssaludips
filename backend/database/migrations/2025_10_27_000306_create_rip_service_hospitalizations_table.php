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
        Schema::create('rip_service_hospitalizations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('rip_invoice_user_id')->constrained();
            $table->string('codPrestador')->nullable();
            $table->string('viaIngresoServicioSalud')->nullable();
            $table->string('fechaInicioAtencion')->nullable();
            $table->string('numAutorizacion')->nullable();
            $table->string('causaMotivoAtencion')->nullable();
            $table->string('codDiagnosticoPrincipal')->nullable();
            $table->string('codDiagnosticoPrincipalE')->nullable();
            $table->string('codDiagnosticoRelacionadoE1')->nullable();
            $table->string('codDiagnosticoRelacionadoE2')->nullable();
            $table->string('codDiagnosticoRelacionadoE3')->nullable();
            $table->string('codComplicacion')->nullable();
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
        Schema::dropIfExists('rip_service_hospitalizations');
    }
};
