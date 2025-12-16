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
        // Table likely already exists from Node.js backend
        if (Schema::hasTable('incidents')) {
            return;
        }

        Schema::create('incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->text('description');
            $table->string('location')->nullable();
            $table->string('locationText')->nullable();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->string('severity');
            $table->string('date')->nullable();
            $table->string('time')->nullable();
            $table->string('status')->default('Open');
            $table->json('photos')->default('[]');
            $table->string('photoUrl')->nullable();
            $table->string('photoKey')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('created_at');
            $table->index('severity');
            $table->index('type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
