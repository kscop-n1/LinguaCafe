<?php

namespace App\Jobs;

use App\Enums\ChapterProcessingStatusEnum;
use App\Models\Chapter;
use App\Models\EncounteredWord;
use App\Models\Phrase;
use App\Services\ChapterService;
// services
use App\Services\QueueStatsService;
use App\Services\VocabularyService;
// models
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessChapter implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private VocabularyService $vocabularyService;

    private ChapterService $chapterService;

    private QueueStatsService $queueStatsService;

    private $userId;

    private $userUuid;

    private $chapterId;

    private $language;

    private $dispatchedAt;

    private $startedAt;

    public function __construct(
        $userId,
        $userUuid,
        $chapterId,
        $language
    ) {
        $this->vocabularyService = new VocabularyService;
        $this->chapterService = new ChapterService;
        $this->queueStatsService = new QueueStatsService;

        $this->userId = $userId;
        $this->userUuid = $userUuid;
        $this->chapterId = $chapterId;
        $this->language = $language;
        $this->dispatchedAt = Carbon::now();
    }

    public function handle()
    {
        try {
            $this->startedAt = Carbon::now();

            $chapter = Chapter::query()
                ->where('id', $this->chapterId)
                ->where('user_id', $this->userId)
                ->first();

            // process chapter text
            $this->chapterService->processChapterText($this->userId, $this->chapterId);

            // index phrases that were created while the job was running
            $phrases = Phrase::where('user_id', $this->userId)
                ->where('language', $this->language)
                ->where('created_at', '>=', $this->startedAt)
                ->where('created_at', '<=', Carbon::now())
                ->get();

            foreach ($phrases as $phrase) {
                $this->vocabularyService->indexPhraseInChapter($chapter->id, $this->userId, $this->language, $phrase);
            }

            $chapter->refresh();
            $this->queueStatsService->insertChapterProcessedStat($chapter, 'finished', $this->dispatchedAt, $this->startedAt);
            $this->broadcastChapterStatusEvent($chapter);
        } catch (\Throwable $e) {
            $this->jobFailed();
            throw $e;
        }
    }

    // Laravel does not pass context to it's own failed() method.
    public function jobFailed()
    {
        $chapter = Chapter::where('id', $this->chapterId)
            ->where('user_id', $this->userId)
            ->first();

        // set chapter processing status to failed
        $chapter->processing_status = ChapterProcessingStatusEnum::FAILED->value;
        $chapter->save();

        $this->queueStatsService->insertChapterProcessedStat($chapter, 'failed', $this->dispatchedAt, $this->startedAt);
        $this->broadcastChapterStatusEvent($chapter);
    }

    private function broadcastChapterStatusEvent(Chapter $chapter): void
    {
        $words = EncounteredWord::select(['id', 'word', 'stage'])
            ->where('user_id', $this->userId)
            ->where('language', $this->language)
            ->get()
            ->keyBy('id')
            ->toArray();

        if ($chapter->processing_status === ChapterProcessingStatusEnum::PROCESSED->value) {
            $chapter->wordCount = $chapter->getWordCounts($words);
        }

        event(new \App\Events\ChapterStateUpdatedEvent($this->userUuid, [
            $chapter->id => [
                'processing_status' => $chapter->processing_status,
                'wordCount' => $chapter->wordCount ?? null,
            ],
        ]));
    }
}
