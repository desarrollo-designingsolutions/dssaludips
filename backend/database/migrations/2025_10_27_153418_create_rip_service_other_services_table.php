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
        Schema::create('rip_service_other_services', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('rip_invoice_user_id')->constrained();
            $table->string('codPrestador')->nullable();
            $table->string('numAutorizacion')->nullable();
            $table->string('idMIPRES')->nullable();
            $table->string('fechaSuministroTecnologia')->nullable();
            $table->string('tipoOS')->nullable();
            $table->string('codTecnologiaSalud')->nullable();
            $table->string('nomTecnologiaSalud')->nullable();
            $table->string('cantidadOS')->nullable();
            $table->string('tipoDocumentoIdentificacion')->nullable();
            $table->string('numDocumentoIdentificacion')->nullable();
            $table->string('vrUnitOS')->nullable();
            $table->string('vrServicio')->nullable();
            $table->string('conceptoRecaudo')->nullable();
            $table->string('valorPagoModerador')->nullable();
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
        Schema::dropIfExists('rip_service_other_services');
    }
};
