<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vacancies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title', 200);
            $table->string('department', 120)->nullable();
            $table->string('location', 120)->nullable();
            $table->enum('employment_type', ['permanent','contract','intern'])->default('permanent');
            $table->text('description')->nullable();
            $table->date('closing_date')->nullable();
            $table->enum('status', ['draft','open','closed','archived'])->default('open');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('vacancies');
    }
};
