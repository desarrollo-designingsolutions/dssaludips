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
        Schema::table('rip_invoices', function (Blueprint $table) {
            $table->renameColumn('note_type', 'tipoNota');
            $table->renameColumn('note_number', 'numNota');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rip_invoices', function (Blueprint $table) {
            $table->renameColumn('tipoNota', 'note_type');
            $table->renameColumn('numNota', 'note_number');
        });
    }
};
