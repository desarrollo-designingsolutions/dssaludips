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
        Schema::create('rip_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('rips_id')->constrained();
            $table->string('invoice_number')->comment('Columna del txt (numFactura), archivo AF');
            $table->string('status')->comment("Estado de la factura");
            $table->string('status_xml')->comment("Estado del XML de la factura");
            $table->string('users_count')->default(0)->comment('cantidad de usaurios en la factura');
            $table->string('path_json')->nullable()->comment('ruta del archivo json');
            $table->string('path_xml')->nullable()->comment('ruta del archivo xml');
            $table->string('path_excel')->nullable()->comment('ruta del archivo excel');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rip_invoices');
    }
};
