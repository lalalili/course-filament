<?php

namespace Lalalili\CourseFilament\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class SyncCourseProductAction
{
    public static function make(string $name = 'syncCourseProduct'): Action
    {
        return Action::make($name)
            ->label((string) config('course-filament.commerce.sync_product_action_label', '同步課程商品'))
            ->icon((string) config('course-filament.commerce.sync_product_action_icon', 'heroicon-o-shopping-bag'))
            ->color('gray')
            ->action(function (Model $record): void {
                $serviceClass = 'Lalalili\\CourseCommerce\\Support\\CourseCommerceProductBindingService';

                if (! class_exists($serviceClass)) {
                    Notification::make()
                        ->title('尚未安裝課程商務套件')
                        ->body('請先安裝 lalalili/course-commerce 後再同步課程商品。')
                        ->danger()
                        ->send();

                    return;
                }

                app($serviceClass)->syncProductForCourse($record);

                Notification::make()
                    ->title('課程商品已同步')
                    ->success()
                    ->send();
            });
    }
}
