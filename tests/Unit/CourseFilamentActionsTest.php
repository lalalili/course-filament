<?php

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Lalalili\CourseCore\Data\CourseReadinessResult;
use Lalalili\CourseFilament\Actions\CheckCourseReadinessAction;
use Lalalili\CourseFilament\Actions\SyncCourseProductAction;
use Lalalili\CourseFilament\Resources\CourseCategories\CourseCategoryResource;
use Lalalili\CourseFilament\Resources\Courses\CourseResource;
use Lalalili\CourseFilament\Resources\Courses\RelationManagers\ChapterRelationManager;
use Lalalili\CourseFilament\Tables\VideoStatusColumns;

it('emits success notification when course is ready with no issues', function (): void {
    $result = new CourseReadinessResult();

    $notification = CheckCourseReadinessAction::notification($result);

    expect($notification)->toBeInstanceOf(Notification::class)
        ->and($notification->getTitle())->toBe('課程已符合發布條件')
        ->and($notification->getBody())->toBe('必要欄位、章節、單元與影片設定已通過檢查。');
});

it('emits success notification with warning body when course is ready but has warnings', function (): void {
    $result = new CourseReadinessResult(
        blockingIssues: [],
        warnings: ['Unit video is still processing.'],
        suggestions: ['Add course tags.'],
    );

    $notification = CheckCourseReadinessAction::notification($result);

    expect($notification)->toBeInstanceOf(Notification::class)
        ->and($notification->getTitle())->toBe('課程已符合發布條件')
        ->and($notification->getBody())->toContain('- Unit video is still processing.')
        ->and($notification->getBody())->toContain('- Add course tags.');
});

it('emits danger notification when course has blocking issues', function (): void {
    $result = new CourseReadinessResult(
        blockingIssues: ['Course title is required.'],
    );

    $notification = CheckCourseReadinessAction::notification($result);

    expect($notification)->toBeInstanceOf(Notification::class)
        ->and($notification->getTitle())->toBe('課程尚未符合發布條件')
        ->and($notification->getBody())->toContain('- Course title is required.');
});

it('builds video status columns with no prefix when prefix is null', function (): void {
    $columns = VideoStatusColumns::make(null);

    expect($columns)->toHaveCount(3)
        ->and($columns[0]->getName())->toBe('provider_status')
        ->and($columns[1]->getName())->toBe('transcode_status')
        ->and($columns[2]->getName())->toBe('duration');
});

it('builds video status columns with empty string prefix', function (): void {
    $columns = VideoStatusColumns::make('');

    expect($columns[0]->getName())->toBe('provider_status');
});

it('includes CourseCategoryResource in package default resources config', function (): void {
    expect(config('course-filament.resources'))->toContain(CourseCategoryResource::class);
});

it('resolves CourseCategoryResource model from course-core config', function (): void {
    config()->set('course-core.models.category', App\Models\CourseCategory::class);

    expect(CourseCategoryResource::getModel())->toBe(App\Models\CourseCategory::class);
});

it('includes ChapterRelationManager in CourseResource relations', function (): void {
    expect(CourseResource::getRelations())->toContain(ChapterRelationManager::class);
});

it('exposes chapterType and unitType as null by default', function (): void {
    $manager = new class () extends ChapterRelationManager {
        public function publicChapterType(): mixed
        {
            return $this->chapterType();
        }

        public function publicUnitType(): mixed
        {
            return $this->unitType();
        }
    };

    expect($manager->publicChapterType())->toBeNull()
        ->and($manager->publicUnitType())->toBeNull();
});

it('host can override chapterType and unitType', function (): void {
    $manager = new class () extends ChapterRelationManager {
        protected function chapterType(): mixed { return 1; }
        protected function unitType(): mixed { return 2; }
        public function publicChapterType(): mixed { return $this->chapterType(); }
        public function publicUnitType(): mixed { return $this->unitType(); }
    };

    expect($manager->publicChapterType())->toBe(1)
        ->and($manager->publicUnitType())->toBe(2);
});

it('SyncCourseProductAction returns a Filament Action instance with correct defaults', function (): void {
    $action = SyncCourseProductAction::make();

    expect($action)->toBeInstanceOf(Action::class)
        ->and($action->getName())->toBe('syncCourseProduct');
});

it('SyncCourseProductAction label and icon follow config', function (): void {
    config()->set('course-filament.commerce.sync_product_action_label', '商品同步');
    config()->set('course-filament.commerce.sync_product_action_icon', 'heroicon-o-shopping-bag');

    $action = SyncCourseProductAction::make();

    expect($action->getLabel())->toBe('商品同步');
});

it('course-commerce package is present in the package test environment', function (): void {
    // course-filament requires course-commerce; the class must exist in the vendor.
    expect(class_exists('Lalalili\\CourseCommerce\\Support\\CourseCommerceProductBindingService'))->toBeTrue();
});
