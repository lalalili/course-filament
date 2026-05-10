<?php

namespace Lalalili\CourseFilament\Resources\Courses;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Lalalili\CourseCore\Contracts\CourseTenantResolver;
use Lalalili\CourseFilament\Resources\Courses\Pages\CreateCourse;
use Lalalili\CourseFilament\Resources\Courses\Pages\EditCourse;
use Lalalili\CourseFilament\Resources\Courses\Pages\ListCourses;

class CourseResource extends Resource
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::BookOpen;

    protected static string|\UnitEnum|null $navigationGroup = 'Courses';

    protected static ?int $navigationSort = 10;

    public static function getModel(): string
    {
        $model = config('course-core.models.course');

        return is_string($model) && class_exists($model) ? $model : Model::class;
    }

    public static function shouldRegisterNavigation(): bool
    {
        $model = config('course-core.models.course');

        if (! is_string($model) || ! class_exists($model)) {
            return false;
        }

        return app(CourseTenantResolver::class)->canAccessAdmin(auth()->user());
    }

    public static function getModelLabel(): string
    {
        return 'Course';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->required()
                ->maxLength(255),
            TextInput::make('slug')
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->columnSpanFull(),
            TextInput::make('status')
                ->required()
                ->default('draft')
                ->maxLength(50),
            Toggle::make('is_free')
                ->label('Free')
                ->default(false),
            TextInput::make('price')
                ->numeric()
                ->default(0)
                ->minValue(0),
            DateTimePicker::make('published_at'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                IconColumn::make('is_free')
                    ->label('Free')
                    ->boolean(),
                TextColumn::make('price')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index'  => ListCourses::route('/'),
            'create' => CreateCourse::route('/create'),
            'edit'   => EditCourse::route('/{record}/edit'),
        ];
    }
}
