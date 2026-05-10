<?php

namespace Lalalili\CourseFilament\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Js;
use ReflectionFunction;

class OpenUploadCenterAction
{
    /**
     * @param  array<string, mixed>|Closure  $context
     */
    public static function make(string $name = 'openUploadCenter', array|Closure $context = []): Action
    {
        return Action::make($name)
            ->label((string) config('course-filament.upload_center.action_label', '大型影片上傳'))
            ->icon((string) config('course-filament.upload_center.action_icon', 'heroicon-o-arrow-up-tray'))
            ->alpineClickHandler(
                fn (?Get $get = null): string => self::script(self::resolveContext($context, $get)),
            );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function script(array $context = []): string
    {
        $payload = array_filter(array_merge([
            'source' => config('course-filament.upload_center.default_source', 'course_unit'),
            'strategy' => config('course-filament.upload_center.default_strategy', 's3_multipart_then_import'),
        ], $context), fn (mixed $value): bool => $value !== null);

        return 'window.__uploadCenter?.open('.Js::from($payload)->toHtml().')';
    }

    /**
     * @param  array<string, mixed>|Closure  $context
     * @return array<string, mixed>
     */
    private static function resolveContext(array|Closure $context, ?Get $get): array
    {
        if (is_array($context)) {
            return $context;
        }

        $reflection = new ReflectionFunction($context);
        $resolvedContext = $reflection->getNumberOfParameters() === 0
            ? $context()
            : $context($get);

        return is_array($resolvedContext) ? $resolvedContext : [];
    }
}
