<?php

namespace Lalalili\CourseFilament\Resources\Courses\Pages;

use Filament\Resources\Pages\CreateRecord;
use Lalalili\CourseFilament\Resources\Courses\CourseResource;

class CreateCourse extends CreateRecord
{
    protected static string $resource = CourseResource::class;
}
