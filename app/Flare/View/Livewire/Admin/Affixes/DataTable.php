<?php

namespace App\Flare\View\Livewire\Admin\Affixes;

use Livewire\Component;
use Livewire\WithPagination;
use App\Flare\Models\ItemAffix;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;

class DataTable extends Component
{
    use WithPagination, WithSorting;

    public $search       = '';
    public $sortField    = 'skill_level_required';
    public $perPage      = 10;
    public $only         = null;
    public $type         = null;
    public $irresistible = false;

    protected $paginationTheme = 'bootstrap';

    public function fetchAffixes() {
        return ItemAffix::dataTableSearch($this->search)
            ->orderBy($this->sortField, $this->sortBy)
            ->paginate($this->perPage);
    }

    public function fetchClassBonusAffixes() {
        return ItemAffix::dataTableSearch($this->search)
            ->where('class_bonus', '>', 0)
            ->orderBy($this->sortField, $this->sortBy)
            ->paginate($this->perPage);
    }

    public function fetchDamageBonusAffixes() {
        return ItemAffix::dataTableSearch($this->search)
            ->where('damage', '>', 0)
            ->orderBy($this->sortField, $this->sortBy)
            ->paginate($this->perPage);
    }

    public function fetchResistanceReductionAffixes() {
        return ItemAffix::dataTableSearch($this->search)
            ->where('resistance_reduction', '>', 0)
            ->orderBy($this->sortField, $this->sortBy)
            ->paginate($this->perPage);
    }

    public function fetchLifeStealingAffixes() {
        return ItemAffix::dataTableSearch($this->search)
            ->whereNotNull('steal_life_amount')
            ->orderBy($this->sortField, $this->sortBy)
            ->paginate($this->perPage);
    }

    public function fetchStatReductionAffixes() {
        return ItemAffix::dataTableSearch($this->search)
            ->where('reduces_enemy_stats', true)
            ->orderBy($this->sortField, $this->sortBy)
            ->paginate($this->perPage);
    }

    public function fetchEntrancingAffixes() {
        return ItemAffix::dataTableSearch($this->search)
            ->where('entranced_chance', '>', 0)
            ->orderBy($this->sortField, $this->sortBy)
            ->paginate($this->perPage);
    }

    public function fetchSpecificStat() {
        return ItemAffix::dataTableSearch($this->search)
            ->where($this->type, '>', 0)
            ->orderBy($this->sortField, $this->sortBy)
            ->paginate($this->perPage);
    }

    public function fetchSkills() {
        return ItemAffix::dataTableSearch($this->search)
            ->where($this->type, '>', 0)
            ->orderBy($this->sortField, $this->sortBy)
            ->paginate($this->perPage);
    }

    public function fetchClassBonus() {
        return ItemAffix::dataTableSearch($this->search)
            ->where($this->type, '>', 0)
            ->orderBy($this->sortField, $this->sortBy)
            ->paginate($this->perPage);
    }

    public function fetchModifiers() {
        if ($this->type === 'base_ac_mod_bonus') {
            $affixes = ItemAffix::dataTableSearch($this->search)
                ->whereNotNull($this->type)
                ->get();

            $affixes = $affixes->filter(function($affix) {
                if (!is_null($affix->base_ac_mod_bonus)) {
                    return $affix->base_ac_mod_bonus > 0.0;
                }
            });

            if ($this->sortBy === 'asc') {
                $affixes = $affixes->sortBy($this->sortField);
            } else {
                $affixes = $affixes->sortByDesc($this->sortField);
            }

            return $affixes->paginate($this->perPage);
        }

        return ItemAffix::dataTableSearch($this->search)
            ->where($this->type, '>', 0)
            ->orderBy($this->sortField, $this->sortBy)
            ->paginate($this->perPage);
    }

    public function fetchDamage() {
        if ($this->irresistible) {
            $affixes = ItemAffix::dataTableSearch($this->search)
                ->where('irresistible_damage', true)
                ->get();
        } else {
            $affixes = ItemAffix::dataTableSearch($this->search)
                ->where('irresistible_damage', false)
                ->where('damage', '>', 0)
                ->get();
        }


        if ($this->sortBy === 'asc') {
            $affixes = $affixes->sortBy($this->sortField);
        } else {
            $affixes = $affixes->sortByDesc($this->sortField);
        }

        return $affixes->paginate($this->perPage);
    }

    public function fetchEntrancing() {
        $affixes = ItemAffix::dataTableSearch($this->search)
            ->whereNotNull('entranced_chance')
            ->get();

        $affixes = $affixes->filter(function($affix) {
            if (!is_null($affix->base_ac_mod_bonus)) {
                return $affix->entranced_chance > 0.0;
            }
        });

        if ($this->sortBy === 'asc') {
            $affixes = $affixes->sortBy($this->sortField);
        } else {
            $affixes = $affixes->sortByDesc($this->sortField);
        }

        return $affixes->paginate($this->perPage);
    }

    public function fetchDevouringLight() {
        $affixes = ItemAffix::dataTableSearch($this->search)
            ->whereNotNull('devouring_light')
            ->get();

        $affixes = $affixes->filter(function($affix) {
            if (!is_null($affix->base_ac_mod_bonus)) {
                return $affix->devouring_light > 0.0;
            }
        });

        if ($this->sortBy === 'asc') {
            $affixes = $affixes->sortBy($this->sortField);
        } else {
            $affixes = $affixes->sortByDesc($this->sortField);
        }

        return $affixes->paginate($this->perPage);
    }

    public function render()
    {
        if ($this->search !== '') {
            $this->page = 1;
        }

        $data = $this->fetchAffixes();

        if (!is_null($this->only)) {
            switch ($this->only) {
                case 'class_bonus':
                    $data =  $this->fetchClassBonusAffixes();
                    break;
                case 'damage':
                    $data = $this->fetchDamageBonusAffixes();
                    break;
                case 'life_stealing':
                    $data = $this->fetchLifeStealingAffixes();
                    break;
                case 'stat_reduction':
                    $data = $this->fetchStatReductionAffixes();
                    break;
                case 'entrancing_chance':
                    $data = $this->fetchEntrancingAffixes();
                    break;
                case 'specific_stat':
                    $data = $this->fetchSpecificStat();
                    break;
                case 'skills':
                    $data = $this->fetchSkills();
                    break;
                case 'modifiers':
                    $data = $this->fetchModifiers();
                    break;
                case 'damage-dealing':
                    $data = $this->fetchDamage();
                    break;
                case 'entrancing':
                    $data = $this->fetchEntrancing();
                    break;
                case 'devouring_light':
                    $data = $this->fetchDevouringLight();
                    break;
                case 'resistance_reduction':
                    $data = $this->fetchResistanceReductionAffixes();
                    break;
                default:
                    $data = $this->fetchAffixes();
                    break;
            }
        }

        return view('components.livewire.admin.affixes.data-table', [
            'itemAffixes' => $data,
        ]);
    }
}
