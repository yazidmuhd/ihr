<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('resumes', function (Blueprint $table) {
            if (!Schema::hasColumn('resumes','ai_parsed'))     $table->json('ai_parsed')->nullable();
            if (!Schema::hasColumn('resumes','ai_parsed_at'))  $table->timestamp('ai_parsed_at')->nullable();
            if (!Schema::hasColumn('resumes','text_excerpt'))  $table->longText('text_excerpt')->nullable();
            if (!Schema::hasColumn('resumes','original_name')) $table->string('original_name')->nullable();
            if (!Schema::hasColumn('resumes','mime'))          $table->string('mime')->nullable();
            if (!Schema::hasColumn('resumes','size'))          $table->unsignedBigInteger('size')->nullable();
            if (!Schema::hasColumn('resumes','path'))          $table->string('path')->nullable(); // local storage path
        });
    }
    public function down(): void {
        Schema::table('resumes', function (Blueprint $table) {
            $cols = ['ai_parsed','ai_parsed_at','text_excerpt','original_name','mime','size','path'];
            foreach ($cols as $c) if (Schema::hasColumn('resumes',$c)) $table->dropColumn($c);
        });
    }
};

