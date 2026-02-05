<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run this migration without wrapping in a single transaction.
     * This avoids "current transaction is aborted" if one optional step fails.
     */
    public $withinTransaction = false;

    protected function pgHasVector(): bool
    {
        // Do NOT attempt CREATE EXTENSION here; just detect existence safely.
        try {
            $row = DB::selectOne("SELECT 1 FROM pg_extension WHERE extname = 'vector'");
            return (bool) $row;
        } catch (\Throwable $e) {
            return false; // not Postgres or no permission — treat as absent
        }
    }

    public function up(): void
    {
        $useVector = DB::getDriverName() === 'pgsql' && $this->pgHasVector();

        /* -------- vacancies columns -------- */
        if (Schema::hasTable('vacancies')) {
            if (!Schema::hasColumn('vacancies', 'canonical_skills')) {
                Schema::table('vacancies', function (Blueprint $table) {
                    $table->json('canonical_skills')->nullable()->after('description');
                });
            }

            if ($useVector) {
                if (!Schema::hasColumn('vacancies', 'jd_embedding_vector')) {
                    try {
                        DB::statement("ALTER TABLE vacancies ADD COLUMN jd_embedding_vector vector(768)");
                    } catch (\Throwable $e) {
                        // ignore if it already exists or type unavailable
                    }
                }
                // optional IVF index
                try {
                    DB::statement("CREATE INDEX IF NOT EXISTS vacancies_jd_embedding_ivf ON vacancies USING ivfflat (jd_embedding_vector vector_l2_ops) WITH (lists = 100)");
                } catch (\Throwable $e) {
                    // ignore if ivfflat not available
                }
            } else {
                if (!Schema::hasColumn('vacancies', 'jd_embedding_json')) {
                    Schema::table('vacancies', function (Blueprint $table) {
                        $table->json('jd_embedding_json')->nullable();
                    });
                }
            }
        }

        /* -------- resumes columns -------- */
        if (Schema::hasTable('resumes')) {
            if ($useVector) {
                if (!Schema::hasColumn('resumes', 'embedding_vector')) {
                    try {
                        DB::statement("ALTER TABLE resumes ADD COLUMN embedding_vector vector(768)");
                    } catch (\Throwable $e) {}
                }
                try {
                    DB::statement("CREATE INDEX IF NOT EXISTS resumes_embedding_ivf ON resumes USING ivfflat (embedding_vector vector_l2_ops) WITH (lists = 100)");
                } catch (\Throwable $e) {}
            } else {
                if (!Schema::hasColumn('resumes', 'embedding_json')) {
                    Schema::table('resumes', function (Blueprint $table) {
                        $table->json('embedding_json')->nullable();
                    });
                }
            }
        }

        /* -------- applications columns -------- */
        if (Schema::hasTable('applications') && !Schema::hasColumn('applications', 'match_score')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->decimal('match_score', 5, 2)->nullable()->after('status'); // 0..100.00
                $table->index(['vacancy_id', 'match_score']);
            });
        }

        /* -------- helper table: resume_embeddings -------- */
        if (Schema::hasTable('resumes') && !Schema::hasTable('resume_embeddings')) {
            if ($useVector) {
                // raw create for vector type
                try {
                    DB::statement("
                        CREATE TABLE resume_embeddings (
                            id BIGSERIAL PRIMARY KEY,
                            resume_id BIGINT NOT NULL REFERENCES resumes(id) ON DELETE CASCADE,
                            embedding_vector vector(768),
                            created_at TIMESTAMP NULL,
                            updated_at TIMESTAMP NULL,
                            UNIQUE(resume_id)
                        )
                    ");
                    DB::statement("CREATE INDEX IF NOT EXISTS resume_embeddings_ivf ON resume_embeddings USING ivfflat (embedding_vector vector_l2_ops) WITH (lists = 100)");
                } catch (\Throwable $e) {
                    // if anything fails, fall back to JSON table
                    if (!Schema::hasTable('resume_embeddings')) {
                        Schema::create('resume_embeddings', function (Blueprint $table) {
                            $table->id();
                            $table->foreignId('resume_id')->constrained('resumes')->cascadeOnDelete();
                            $table->json('embedding_json')->nullable();
                            $table->timestamps();
                            $table->unique('resume_id');
                        });
                    }
                }
            } else {
                Schema::create('resume_embeddings', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('resume_id')->constrained('resumes')->cascadeOnDelete();
                    $table->json('embedding_json')->nullable();
                    $table->timestamps();
                    $table->unique('resume_id');
                });
            }
        }
    }

    public function down(): void
    {
        // drop helper table first
        Schema::dropIfExists('resume_embeddings');

        // drop added columns (wrapped in try/catch for idempotency)
        if (Schema::hasTable('applications')) {
            try { DB::statement("ALTER TABLE applications DROP COLUMN IF EXISTS match_score"); } catch (\Throwable $e) {}
        }
        if (Schema::hasTable('resumes')) {
            try { DB::statement("ALTER TABLE resumes DROP COLUMN IF EXISTS embedding_vector"); } catch (\Throwable $e) {}
            try { DB::statement("ALTER TABLE resumes DROP COLUMN IF EXISTS embedding_json"); } catch (\Throwable $e) {}
        }
        if (Schema::hasTable('vacancies')) {
            try { DB::statement("ALTER TABLE vacancies DROP COLUMN IF EXISTS jd_embedding_vector"); } catch (\Throwable $e) {}
            try { DB::statement("ALTER TABLE vacancies DROP COLUMN IF EXISTS jd_embedding_json"); } catch (\Throwable $e) {}
            try { DB::statement("ALTER TABLE vacancies DROP COLUMN IF EXISTS canonical_skills"); } catch (\Throwable $e) {}
        }
    }
};
