<?php

namespace Lalalili\CourseFilament\Tests;

use Lalalili\CourseCore\CourseCoreServiceProvider;
use Lalalili\CourseFilament\CourseFilamentServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            CourseCoreServiceProvider::class,
            CourseFilamentServiceProvider::class,
        ];
    }
}
