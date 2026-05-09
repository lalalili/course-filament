<?php

use Lalalili\CourseFilament\CourseFilamentPlugin;
use Lalalili\CourseFilament\Pages\CoursePackageHealth;
use Lalalili\CourseFilament\Resources\Courses\CourseResource;

it('provides default course resources and health page through config', function (): void {
    expect(config('course-filament.resources'))->toContain(CourseResource::class)
        ->and(config('course-filament.pages'))->toContain(CoursePackageHealth::class);
});

it('uses a stable plugin id', function (): void {
    expect(CourseFilamentPlugin::make()->getId())->toBe('course');
});
