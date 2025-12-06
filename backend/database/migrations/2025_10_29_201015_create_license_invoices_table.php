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
        Schema::create('license_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_license_id')->constrained("company_licenses"); // A qué licencia pertenece
            $table->string('invoice_number'); // ej: INV-2025-001
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_due', 12, 2);
            $table->string('status');
            $table->string('pdf_path')->nullable(); // futuro: storage/invoices/...
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_invoices');
    }
};
