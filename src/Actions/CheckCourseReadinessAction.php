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
                $result = app(CourseReadinessService::class)->evaluate(
                    course: $record,
                    requireProduct: (bool) config('course-filament.readiness.require_product', false),
                    requireReadyVideos: (bool) config('course-filament.readiness.require_ready_videos', false),
                );

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
}
