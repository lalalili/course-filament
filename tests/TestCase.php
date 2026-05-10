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

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        config()->set('course-filament', require __DIR__.'/../config/course-filament.php');
        config()->set('course-core', require __DIR__.'/../../course-core/config/course-core.php');
    }
}
