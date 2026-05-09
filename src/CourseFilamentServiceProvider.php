<?php

namespace Lalalili\CourseFilament;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CourseFilamentServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('course-filament')
            ->hasConfigFile('course-filament');
    }
}
