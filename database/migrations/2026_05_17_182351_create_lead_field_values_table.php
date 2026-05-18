<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_field_values', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lead_id')->constrained()->cascadeOnDelete();
            $table->string('field_key');
            $table->text('field_value')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['lead_id', 'field_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_field_values');
    }
};
