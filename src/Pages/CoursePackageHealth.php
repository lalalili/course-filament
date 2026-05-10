<?php

namespace Lalalili\CourseFilament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Route;
use Lalalili\CourseCore\Contracts\CourseTenantResolver;

class CoursePackageHealth extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::Cog6Tooth;

    protected static string|\UnitEnum|null $navigationGroup = 'Courses';

    protected static ?string $title = 'Course Package';

    protected string $view = 'course-filament::pages.course-package-health';

    public function getTitle(): string|Htmlable
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
            'course_model'                    => config('course-core.models.course'),
            'category_model'                  => config('course-core.models.category'),
            'history_model'                   => config('course-core.models.history'),
            'access_resolver'                 => config('course-core.access_resolver'),
            'tenant_resolver'                 => config('course-core.tenant_resolver'),
            'product_resolver'                => config('course-core.product_resolver'),
            'video_provider'                  => config('course-core.video_provider'),
            'default_video_platform'          => config('course-core.default_video_platform'),
            'video_staging_disk'              => config('course-core.video_staging_disk'),
            'readiness_requires_product'      => config('course-filament.readiness.require_product') ? 'yes' : 'no',
            'readiness_requires_ready_videos' => config('course-filament.readiness.require_ready_videos') ? 'yes' : 'no',
            'upload_center_enabled'           => config('course-filament.upload_center.enabled') ? 'enabled' : 'disabled',
            'upload_center_view'              => config('course-filament.upload_center.view'),
            'upload_center_routes'            => $this->uploadCenterRoutesStatus(),
            'provider_refresh_job'            => config('course-filament.upload_center.refresh_job'),
            'course_commerce_installed'       => class_exists('Lalalili\\CourseCommerce\\Support\\CourseCommerceProductBindingService') ? 'yes' : 'no',
            'video_upload_installed'          => class_exists('Lalalili\\VideoUpload\\Services\\VideoUploadService') ? 'yes' : 'no',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function getMissingUploadCenterRoutes(): array
    {
        return collect(config('course-filament.upload_center.required_routes', []))
            ->filter(fn (string $route): bool => ! Route::has($route))
            ->values()
            ->all();
    }

    private function uploadCenterRoutesStatus(): string
    {
        $missingRoutes = $this->getMissingUploadCenterRoutes();

        if ($missingRoutes === []) {
            return 'registered';
        }

        return 'missing: '.implode(', ', $missingRoutes);
    }
}
