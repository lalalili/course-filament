<?php

namespace Lalalili\CourseFilament\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Lalalili\VideoUpload\Models\Video;

class RefreshVideoStatusAction
{
    public static function make(string $name = 'refreshVideoStatus'): Action
    {
        return Action::make($name)
            ->label((string) config('course-filament.video.refresh_action_label', '更新影片狀態'))
            ->icon((string) config('course-filament.video.refresh_action_icon', 'heroicon-o-arrow-path'))
            ->color('gray')
            ->action(function (Model $record): void {
                $serviceClass = 'Lalalili\\VideoUpload\\Services\\VideoUploadService';

                if (! class_exists($serviceClass)) {
                    Notification::make()
                        ->title('尚未安裝影片上傳套件')
                        ->body('請先安裝 lalalili/video-upload 後再更新影片狀態。')
                        ->danger()
                        ->send();

                    return;
                }

                $video = self::videoFromRecord($record);

                if (! $video instanceof Video) {
                    Notification::make()
                        ->title('找不到影片')
                        ->warning()
                        ->send();

                    return;
                }

                app($serviceClass)->refresh($video);

                Notification::make()
                    ->title('影片狀態已更新')
                    ->success()
                    ->send();
            });
    }

    private static function videoFromRecord(Model $record): ?Video
    {
        $relation = (string) config('course-filament.video.relation', 'video');

        if ($relation !== '' && $record->relationLoaded($relation)) {
            $video = $record->getRelation($relation);

            return $video instanceof Video ? $video : null;
        }

        if ($relation !== '' && method_exists($record, $relation)) {
            $video = $record->{$relation}()->getResults();

            return $video instanceof Video ? $video : null;
        }

        return null;
    }
}
