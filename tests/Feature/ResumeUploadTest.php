<?php

namespace Tests\Feature;

use App\Jobs\ParseResume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResumeUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.uploads_disk' => 's3']);
        Storage::fake('s3');
        Queue::fake();
    }

    public function test_upload_stores_the_file_on_the_uploads_disk_and_queues_parsing(): void
    {
        [$user, $applicantId] = $this->applicant();

        $this->actingAs($user)->post('/app/resume', [
            'resume' => UploadedFile::fake()->create('aina_cv.pdf', 120, 'application/pdf'),
        ])->assertRedirect();

        $resume = DB::table('resumes')->where('applicant_id', $applicantId)->first();
        $this->assertSame('aina_cv.pdf', $resume->filename);
        $this->assertSame('pending', $resume->ai_status);
        $this->assertTrue($resume->is_active);   // first résumé becomes active
        Storage::disk('s3')->assertExists($resume->path);

        Queue::assertPushedOn('high', ParseResume::class, fn ($job) => $job->resumeId === (int) $resume->id);
    }

    public function test_second_upload_is_not_active_and_activation_switches(): void
    {
        [$user, $applicantId] = $this->applicant();
        foreach (['one.pdf', 'two.pdf'] as $name) {
            $this->actingAs($user)->post('/app/resume', ['resume' => UploadedFile::fake()->create($name, 50, 'application/pdf')]);
        }

        $rows = DB::table('resumes')->where('applicant_id', $applicantId)->orderBy('id')->get();
        $this->assertSame([true, false], $rows->pluck('is_active')->all());

        $this->actingAs($user)->patch("/app/resume/{$rows[1]->id}/activate")->assertRedirect();
        $this->assertSame([false, true], DB::table('resumes')->where('applicant_id', $applicantId)->orderBy('id')->pluck('is_active')->all());
    }

    public function test_only_pdf_and_word_files_up_to_5mb_are_accepted(): void
    {
        [$user] = $this->applicant();

        $this->actingAs($user)->post('/app/resume', ['resume' => UploadedFile::fake()->create('cv.exe', 10)])
            ->assertSessionHasErrors('resume');
        $this->actingAs($user)->post('/app/resume', ['resume' => UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf')])
            ->assertSessionHasErrors('resume');

        $this->assertSame(0, DB::table('resumes')->count());
    }

    public function test_deleting_removes_the_file_and_other_users_cannot_touch_it(): void
    {
        [$owner, $applicantId] = $this->applicant();
        [$other] = $this->applicant();

        $this->actingAs($owner)->post('/app/resume', ['resume' => UploadedFile::fake()->create('cv.pdf', 50, 'application/pdf')]);
        $resume = DB::table('resumes')->where('applicant_id', $applicantId)->first();

        $this->actingAs($other)->delete("/app/resume/{$resume->id}")->assertNotFound();
        Storage::disk('s3')->assertExists($resume->path);

        $this->actingAs($owner)->delete("/app/resume/{$resume->id}")->assertRedirect();
        Storage::disk('s3')->assertMissing($resume->path);
        $this->assertDatabaseMissing('resumes', ['id' => $resume->id]);
    }
}
