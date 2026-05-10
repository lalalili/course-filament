<?php

use Filament\Actions\Action;
use Lalalili\CourseCore\Data\CourseReadinessResult;
use Lalalili\CourseFilament\Actions\CheckCourseReadinessAction;
use Lalalili\CourseFilament\Actions\OpenUploadCenterAction;
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

it('ships disabled upload center integration defaults for host opt in', function (): void {
    expect(config('course-filament.upload_center.enabled'))->toBeFalse()
        ->and(config('course-filament.upload_center.default_strategy'))->toBe('s3_multipart_then_import')
        ->and(config('course-filament.upload_center.concurrency'))->toBe(2)
        ->and(config('course-filament.upload_center.s3_part_size'))->toBe(8 * 1024 * 1024)
        ->and(config('course-filament.upload_center.required_routes'))->toContain('admin.upload-center.videos.store');
});

it('ships reusable course readiness action defaults for host opt in', function (): void {
    expect(config('course-filament.readiness.require_product'))->toBeFalse()
        ->and(config('course-filament.readiness.require_ready_videos'))->toBeFalse()
        ->and(CheckCourseReadinessAction::make())->toBeInstanceOf(Action::class);
});

it('formats course readiness notifications for blocking issues', function (): void {
    $notification = CheckCourseReadinessAction::notification(new CourseReadinessResult(
        blockingIssues: ['Course title is required.'],
        warnings: ['Course unit video is still processing.'],
    ));

    expect($notification->getTitle())->toBe('課程尚未符合發布條件')
        ->and($notification->getBody())->toContain('- Course title is required.')
        ->and($notification->getBody())->toContain('- Course unit video is still processing.');
});

it('builds a reusable upload center action payload', function (): void {
    $script = OpenUploadCenterAction::script([
        'course_id'         => 5,
        'course_chapter_id' => 9,
    ]);

    expect(OpenUploadCenterAction::make())->toBeInstanceOf(Action::class)
        ->and($script)->toContain('window.__uploadCenter?.open')
        ->and($script)->toContain('s3_multipart_then_import')
        ->and($script)->toContain('course_id')
        ->and($script)->toContain('course_chapter_id');
});

it('reports missing upload center routes on the package health page', function (): void {
    $page = new CoursePackageHealth();

    expect($page->getMissingUploadCenterRoutes())->toContain('admin.upload-center.videos.store')
        ->and($page->getConfigurationStatus())->toHaveKeys([
            'product_resolver',
            'readiness_requires_product',
            'readiness_requires_ready_videos',
        ]);
});
