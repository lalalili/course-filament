<?php

namespace Lalalili\CourseFilament\Resources\Courses\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Lalalili\CourseFilament\Actions\CheckCourseReadinessAction;
use Lalalili\CourseFilament\Actions\SyncCourseProductAction;
use Lalalili\CourseFilament\Resources\Courses\CourseResource;

class EditCourse extends EditRecord
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CheckCourseReadinessAction::make(),
            SyncCourseProductAction::make(),
            DeleteAction::make(),
        ];
    }
}
