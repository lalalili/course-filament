<?php

namespace Lalalili\CourseFilament\Support;

use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\Factory as ViewFactory;

class UploadCenterRenderHook
{
    public static function register(Panel $panel, string $hook = PanelsRenderHook::BODY_END): void
    {
        if (! config('course-filament.upload_center.enabled', false)) {
            return;
        }

        $panel->renderHook(
            $hook,
            fn () => auth()->check()
                ? app(ViewFactory::class)->make(self::uploadCenterView())
                : '',
        );
    }

    private static function uploadCenterView(): string
    {
        $view = config('course-filament.upload_center.view', 'course-filament::partials.upload-center');

        return is_string($view) ? $view : 'course-filament::partials.upload-center';
    }
}
