<?php

namespace Lalalili\CourseFilament\Resources\Courses\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ChapterRelationManager extends RelationManager
{
    protected static string $relationship = 'chapters';

    /**
     * Return the value to set for the chapter `type` field when creating a chapter.
     * Override in host to return the enum value or integer for the chapter type.
     */
    protected function chapterType(): mixed
    {
        return null;
    }

    /**
     * Return the value to set for the unit `type` field when creating a unit.
     * Override in host to return the enum value or integer for the unit type.
     */
    protected function unitType(): mixed
    {
        return null;
    }

    public function form(Schema $schema): Schema
    {
        $components = [
            TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->columnSpanFull()
                ->live(),
        ];

        if ($this->chapterType() !== null) {
            $components[] = Hidden::make('type')->default($this->chapterType());
        }

        $components[] = Repeater::make('units')
            ->relationship()
            ->schema($this->buildUnitSchema())
            ->columnSpanFull()
            ->reorderableWithDragAndDrop()
            ->orderColumn('sort')
            ->collapsed()
            ->addActionLabel('Add Unit')
            ->itemLabel(function (array $state): string {
                $title = $state['title'] ?? '';
                $isFree = $state['isFree'] ?? false;

                return $isFree ? "{$title} (Free)" : $title;
            });

        return $schema->components($components);
    }

    /**
     * Build the schema for each unit row inside the Repeater.
     * Override in host subclass to add video selection, upload center, etc.
     *
     * @return array<int, mixed>
     */
    protected function buildUnitSchema(): array
    {
        $schema = [];

        if ($this->unitType() !== null) {
            $schema[] = Hidden::make('type')->default($this->unitType());
        }

        $schema[] = TextInput::make('title')
            ->required()
            ->maxLength(255)
            ->live();

        $schema[] = Toggle::make('isFree')
            ->label('Free')
            ->default(false)
            ->dehydrated();

        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('units_count')
                    ->label('Units')
                    ->getStateUsing(fn ($record): int => $record->units()->count()),
                TextColumn::make('chapter_duration')
                    ->label('Duration'),
            ])
            ->reorderable('sort')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
