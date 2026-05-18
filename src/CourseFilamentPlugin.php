<?php

namespace Lalalili\CourseFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class CourseFilamentPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'course';
    }

    public function register(Panel $panel): void
    {
        $configuredResources = config('course-filament.resources', []);
        $resources = collect(is_array($configuredResources) ? $configuredResources : [])
            ->filter(fn (string $resource): bool => class_exists($resource))
            ->unique()
            ->values()
            ->all();

        if ($resources !== []) {
            $panel->resources($resources);
        }

        $configuredPages = config('course-filament.pages', []);
        $pages = collect(is_array($configuredPages) ? $configuredPages : [])
            ->filter(fn (string $page): bool => class_exists($page))
            ->unique()
            ->values()
            ->all();

        if ($pages !== []) {
            $panel->pages($pages);
        }
    }

    public function boot(Panel $panel): void {}
}
