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
        Schema::create('license_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_license_id')->constrained("company_licenses"); // A qué licencia pertenece
            $table->decimal('amount', 12, 2);    // cuánto se pagó
            $table->timestamp('paid_at');        // fecha y hora del pago
            $table->string('method');            // ej: "transferencia", "stripe", "efectivo"
            $table->text('notes')->nullable();   // comentarios del admin
            $table->foreignUuid('created_by')->constrained("users");          // admin que registró el pago
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_payments');
    }
};
