# Course Filament

Filament admin UI integration layer for `lalalili/course-core`.

## Features

- Registers host course resources through a Filament panel plugin.
- Keeps resources configurable so each application can provide its own resource classes.
- Depends on `lalalili/course-core` for shared contracts and model configuration.

## Installation

Require the package through Composer:

```bash
composer require lalalili/course-filament
```

Publish and customize the configuration:

```bash
php artisan vendor:publish --tag=course-filament-config
```

Configure the resource classes provided by the host application:

```php
return [
    'resources' => [
        App\Filament\Resources\Courses\CourseResource::class,
        App\Filament\Resources\CourseCategories\CourseCategoryResource::class,
    ],
];
```

Register the plugin in a Filament panel provider:

```php
use Lalalili\CourseFilament\CourseFilamentPlugin;

$panel->plugins([
    CourseFilamentPlugin::make(),
]);
```
