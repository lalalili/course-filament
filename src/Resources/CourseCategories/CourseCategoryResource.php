<?php

namespace Lalalili\CourseFilament\Resources\CourseCategories;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
use Illuminate\Support\Str;
use Lalalili\CourseCore\Contracts\CourseTenantResolver;
use Lalalili\CourseFilament\Resources\CourseCategories\Pages\CreateCourseCategory;
use Lalalili\CourseFilament\Resources\CourseCategories\Pages\EditCourseCategory;
use Lalalili\CourseFilament\Resources\CourseCategories\Pages\ListCourseCategories;

class CourseCategoryResource extends Resource
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::RectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Courses';

    protected static ?int $navigationSort = 20;

    public static function getModel(): string
    {
        $model = config('course-core.models.category');

        return is_string($model) && is_subclass_of($model, Model::class) ? $model : Model::class;
    }

    public static function shouldRegisterNavigation(): bool
    {
        $model = config('course-core.models.category');

        if (! is_string($model) || ! class_exists($model)) {
            return false;
        }

        return app(CourseTenantResolver::class)->canAccessAdmin(auth()->user());
    }

    public static function getModelLabel(): string
    {
        return 'Course Category';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Course Categories';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(100)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state ?? ''))),
            TextInput::make('slug')
                ->required()
                ->maxLength(100),
            TextInput::make('sort')
                ->numeric()
                ->default(0)
                ->minValue(0),
            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sort')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort')
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
            'index' => ListCourseCategories::route('/'),
            'create' => CreateCourseCategory::route('/create'),
            'edit' => EditCourseCategory::route('/{record}/edit'),
        ];
    }
}
