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
    Schema::create('conversion_files', function (Blueprint $table) {
        $table->id();
        $table->foreignId('conversion_id')->constrained()->cascadeOnDelete();
        $table->string('original_name');
        $table->string('input_path');
        $table->string('output_path')->nullable();
        $table->string('status')->default('queued'); // queued|processing|done|failed
        $table->text('error')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversion_files');
    }
};
