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
        Schema::create('rip_invoice_users', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('rip_invoice_id')->constrained();
            $table->string('tipoDocumentoIdentificacion')->nullable();
            $table->string('numDocumentoIdentificacion')->nullable();
            $table->string('tipoUsuario')->nullable();
            $table->string('fechaNacimiento')->nullable();
            $table->string('codSexo')->nullable();
            $table->string('codPaisResidencia')->nullable();
            $table->string('codMunicipioResidencia')->nullable();
            $table->string('codZonaTerritorialResidencia')->nullable();
            $table->string('incapacidad')->nullable();
            $table->string('codPaisOrigen')->nullable();
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
        Schema::dropIfExists('rip_invoice_users');
    }
};
