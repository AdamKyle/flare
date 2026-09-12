<?php

namespace App\Admin\Races\Exports\Sheets;

use App\Flare\Models\GameRace;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class RacesSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    /**
     * Return every Race, ordered deterministically by name.
     */
    public function collection(): Collection
    {
        return GameRace::orderBy('name')->orderBy('id')->get();
    }

    /**
     * Map a Race into its workbook row.
     *
     * @param mixed $gameRace
     */
    public function map($gameRace): array
    {
        return [
            $gameRace->name,
            $gameRace->description,
            $gameRace->image_path,
        ];
    }

    /**
     * Return the Races workbook column headings.
     */
    public function headings(): array
    {
        return ['name', 'description', 'image_path'];
    }

    /**
     * Return the Races workbook sheet title.
     */
    public function title(): string
    {
        return 'Races';
    }
}
