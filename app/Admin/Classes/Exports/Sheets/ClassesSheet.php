<?php

namespace App\Admin\Classes\Exports\Sheets;

use App\Flare\Models\GameClass;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class ClassesSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    /**
     * Return every Class, ordered deterministically by name.
     *
     * @return Collection<int, GameClass> Classes to export.
     */
    public function collection(): Collection
    {
        return GameClass::with(['primaryClassRequired', 'secondaryClassRequired'])
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    /**
     * Map a Class into its workbook row, using the prerequisite Classes' names rather than ids.
     *
     * @param  GameClass  $gameClass  Class to map.
     * @return array<int, mixed> Workbook row values.
     */
    public function map($gameClass): array
    {
        return [
            $gameClass->id,
            $gameClass->name,
            $gameClass->description,
            $gameClass->damage_stat,
            $gameClass->to_hit_stat,
            $gameClass->str_mod,
            $gameClass->dur_mod,
            $gameClass->dex_mod,
            $gameClass->chr_mod,
            $gameClass->int_mod,
            $gameClass->agi_mod,
            $gameClass->focus_mod,
            $gameClass->accuracy_mod,
            $gameClass->dodge_mod,
            $gameClass->defense_mod,
            $gameClass->looting_mod,
            $gameClass->primaryClassRequired?->name,
            $gameClass->secondaryClassRequired?->name,
            $gameClass->primary_required_class_level,
            $gameClass->secondary_required_class_level,
        ];
    }

    /**
     * Return the Classes workbook column headings.
     *
     * @return array<int, string> Workbook column headings.
     */
    public function headings(): array
    {
        return [
            'id', 'name', 'description', 'damage_stat', 'to_hit_stat',
            'str_mod', 'dur_mod', 'dex_mod', 'chr_mod', 'int_mod', 'agi_mod', 'focus_mod',
            'accuracy_mod', 'dodge_mod', 'defense_mod', 'looting_mod',
            'primary_required_class_id', 'secondary_required_class_id',
            'primary_required_class_level', 'secondary_required_class_level',
        ];
    }

    /**
     * Return the Classes workbook sheet title.
     *
     * @return string Classes workbook sheet title.
     */
    public function title(): string
    {
        return 'Classes';
    }
}
