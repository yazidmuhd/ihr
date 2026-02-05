<?php

class EmbedResumeJob implements ShouldQueue {
  public function __construct(public int $resumeId) {}
  public function handle() {
    $resume = Resume::findOrFail($this->resumeId);
    $vec = app(Embeddings::class)->embed($resume->extracted_text); // returns float[]
    DB::statement('UPDATE resumes SET embedding = ? WHERE id = ?', [ toPgVector($vec), $resume->id ]);
    // If the resume is tied to an application, score it:
    Application::where('resume_id',$resume->id)->get()
      ->each(fn($app) => dispatch(new ScoreApplicationJob($app->id)));
  }
}
