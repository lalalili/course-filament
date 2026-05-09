<?php

namespace Lalalili\CourseFilament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Lalalili\CourseCore\Contracts\CourseTenantResolver;

class CoursePackageHealth extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = Heroicon::Cog6Tooth;

    protected static string | \UnitEnum | null $navigationGroup = 'Courses';

    protected static ?string $title = 'Course Package';

    protected string $view = 'course-filament::pages.course-package-health';

    public function getTitle(): string | Htmlable
    {
        return 'Course Package';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return app(CourseTenantResolver::class)->canAccessAdmin(auth()->user());
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfigurationStatus(): array
    {
        return [
            'course_model'    => config('course-core.models.course'),
            'category_model'  => config('course-core.models.category'),
            'history_model'   => config('course-core.models.history'),
            'access_resolver' => config('course-core.access_resolver'),
            'tenant_resolver' => config('course-core.tenant_resolver'),
            'video_provider'  => config('course-core.video_provider'),
        ];
    }
}
