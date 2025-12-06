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
        Schema::create('company_licenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained("companies"); // Relación con la tabla companies
            $table->foreignUuid('license_id')->constrained("licenses"); // Plantilla base (de licenses)
            $table->string('custom_name')->nullable(); // ej: "Plan XYZ - 2025"
            $table->date('start_date');
            $table->date('end_date');
            $table->date('renewal_date')->nullable();
            $table->string('status');
            $table->boolean('is_paid')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->decimal('total_due', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_licenses');
    }
};
