<?php

namespace Lalalili\CourseFilament\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Lalalili\CourseCore\Data\CourseReadinessResult;
use Lalalili\CourseCore\Services\CourseReadinessService;

class CheckCourseReadinessAction
{
    public static function make(string $name = 'checkCourseReadiness'): Action
    {
        return Action::make($name)
            ->label((string) config('course-filament.readiness.action_label', '檢查發布條件'))
            ->icon((string) config('course-filament.readiness.action_icon', 'heroicon-o-clipboard-document-check'))
            ->color('gray')
            ->action(function (Model $record): void {
                $record->loadMissing(self::relations());

                $result = app(CourseReadinessService::class)->evaluate(
                    course: $record,
                    requireProduct: (bool) config('course-filament.readiness.require_product', false),
                    requireReadyVideos: (bool) config('course-filament.readiness.require_ready_videos', false),
                );

                self::recordReviewLog($record, $result);
                self::notification($result)->send();
            });
    }

    public static function notification(CourseReadinessResult $result): Notification
    {
        $notification = Notification::make()
            ->title(self::title($result))
            ->body(self::body($result));

        if ($result->isReady()) {
            return $notification->success();
        }

        return $notification->danger();
    }

    private static function title(CourseReadinessResult $result): string
    {
        return $result->isReady()
            ? '課程已符合發布條件'
            : '課程尚未符合發布條件';
    }

    private static function body(CourseReadinessResult $result): string
    {
        $lines = [
            ...$result->blockingIssues,
            ...$result->warnings,
            ...$result->suggestions,
        ];

        if ($lines === []) {
            return '必要欄位、章節、單元與影片設定已通過檢查。';
        }

        return collect($lines)
            ->map(fn (string $line): string => "- {$line}")
            ->implode("\n");
    }

    /**
     * @return list<string>
     */
    private static function relations(): array
    {
        $relations = config('course-filament.readiness.relations', [
            'detail',
            'product',
            'chapters.units.video',
        ]);

        if (! is_array($relations)) {
            return [];
        }

        return array_values(array_filter($relations, is_string(...)));
    }

    private static function recordReviewLog(Model $record, CourseReadinessResult $result): void
    {
        $modelClass = config('course-filament.readiness.review_log_model');

        if (! is_string($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return;
        }

        /** @var class-string<Model> $modelClass */
        $modelClass::query()->create([
            'course_id' => $record->getKey(),
            'actor_id' => auth()->id(),
            'from_status' => data_get($record, 'status'),
            'to_status' => data_get($record, 'status'),
            'action' => 'readiness_check',
            'comment' => $result->isReady() ? 'ready' : 'blocked',
            'readiness_snapshot' => [
                'blocking_issues' => $result->blockingIssues,
                'warnings' => $result->warnings,
                'suggestions' => $result->suggestions,
            ],
        ]);
    }
}
