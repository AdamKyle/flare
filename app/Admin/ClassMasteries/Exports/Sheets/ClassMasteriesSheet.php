<?php

namespace App\Admin\ClassMasteries\Exports\Sheets;

use App\Flare\Models\GameClassSpecial;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class ClassMasteriesSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    /**
     * Return every Class Mastery, ordered deterministically by name.
     */
    public function collection(): Collection
    {
        return GameClassSpecial::orderBy('name')->orderBy('id')->get();
    }

    /**
     * Map a Class Mastery into its workbook row.
     *
     * @param mixed $gameClassSpecial
     */
    public function map($gameClassSpecial): array
    {
        return [
            $gameClassSpecial->id,
            $gameClassSpecial->game_class_id,
            $gameClassSpecial->name,
            $gameClassSpecial->description,
            $gameClassSpecial->requires_class_rank_level,
            $gameClassSpecial->specialty_damage,
            $gameClassSpecial->increase_specialty_damage_per_level,
            $gameClassSpecial->specialty_damage_uses_damage_stat_amount,
            $gameClassSpecial->base_damage_mod,
            $gameClassSpecial->base_ac_mod,
            $gameClassSpecial->base_healing_mod,
            $gameClassSpecial->base_spell_damage_mod,
            $gameClassSpecial->health_mod,
            $gameClassSpecial->base_damage_stat_increase,
            $gameClassSpecial->attack_type_required,
            $gameClassSpecial->spell_evasion,
            $gameClassSpecial->affix_damage_reduction,
            $gameClassSpecial->healing_reduction,
            $gameClassSpecial->skill_reduction,
            $gameClassSpecial->resistance_reduction,
        ];
    }

    /**
     * Return the Class Masteries workbook column headings.
     */
    public function headings(): array
    {
        return [
            'id', 'game_class_id', 'name', 'description', 'requires_class_rank_level',
            'specialty_damage', 'increase_specialty_damage_per_level', 'specialty_damage_uses_damage_stat_amount',
            'base_damage_mod', 'base_ac_mod', 'base_healing_mod', 'base_spell_damage_mod',
            'health_mod', 'base_damage_stat_increase', 'attack_type_required',
            'spell_evasion', 'affix_damage_reduction', 'healing_reduction', 'skill_reduction', 'resistance_reduction',
        ];
    }

    /**
     * Return the Class Masteries workbook sheet title.
     */
    public function title(): string
    {
        return 'Class Masteries';
    }
}
