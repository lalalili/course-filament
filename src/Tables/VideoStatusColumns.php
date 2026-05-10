<?php

namespace Lalalili\CourseFilament\Tables;

use Filament\Tables\Columns\TextColumn;

class VideoStatusColumns
{
    /**
     * @return list<TextColumn>
     */
    public static function make(?string $prefix = null): array
    {
        $prefix = $prefix === null || $prefix === '' ? '' : rtrim($prefix, '.').'.';

        return [
            TextColumn::make($prefix.'provider_status')
                ->label('Provider')
                ->badge()
                ->toggleable(),
            TextColumn::make($prefix.'transcode_status')
                ->label('Transcode')
                ->badge()
                ->toggleable(),
            TextColumn::make($prefix.'duration')
                ->label('Duration')
                ->numeric()
                ->toggleable(),
        ];
    }
}
