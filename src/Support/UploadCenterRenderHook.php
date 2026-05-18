<?php

namespace Lalalili\CourseFilament\Support;

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;

class UploadCenterRenderHook
{
    public static function register(string $hook = PanelsRenderHook::BODY_END): void
    {
        if (! config('course-filament.upload_center.enabled', false)) {
            return;
        }

        FilamentView::registerRenderHook(
            $hook,
            fn () => view((string) config('course-filament.upload_center.view', 'course-filament::partials.upload-center')),
        );
    }
}
