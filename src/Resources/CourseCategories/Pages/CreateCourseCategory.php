<?php

namespace Lalalili\CourseFilament\Resources\CourseCategories\Pages;

use Filament\Resources\Pages\CreateRecord;
use Lalalili\CourseFilament\Resources\CourseCategories\CourseCategoryResource;

class CreateCourseCategory extends CreateRecord
{
    protected static string $resource = CourseCategoryResource::class;
}
