<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('applicants', function (Blueprint $t) {
            if (!Schema::hasColumn('applicants','name'))          $t->string('name')->nullable()->after('email');
            if (!Schema::hasColumn('applicants','headline'))      $t->string('headline', 180)->nullable();
            if (!Schema::hasColumn('applicants','phone'))         $t->string('phone', 40)->nullable();
            if (!Schema::hasColumn('applicants','location'))      $t->string('location', 120)->nullable();
            if (!Schema::hasColumn('applicants','skills_text'))   $t->text('skills_text')->nullable(); // comma/line separated
            if (!Schema::hasColumn('applicants','linkedin_url'))  $t->string('linkedin_url')->nullable();
            if (!Schema::hasColumn('applicants','github_url'))    $t->string('github_url')->nullable();
            if (!Schema::hasColumn('applicants','portfolio_url')) $t->string('portfolio_url')->nullable();
            if (!Schema::hasColumn('applicants','website_url'))   $t->string('website_url')->nullable();
        });
    }
    public function down(): void {
        Schema::table('applicants', function (Blueprint $t) {
            $t->dropColumn([
                'name','headline','phone','location',
                'skills_text','linkedin_url','github_url',
                'portfolio_url','website_url'
            ]);
        });
    }
};
