<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use App\Flare\Models\ArtifactProperty;
use App\Flare\Models\ItemAffix;

class Item extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'type',
        'base_damage',
        'cost',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'base_damage' => 'integer',
        'cost'        => 'integer',
    ];

    public function artifactProperty() {
        return $this->hasOne(ArtifactProperty::class);
    }

    public function itemAffixes() {
        return $this->hasMany(ItemAffix::class);
    }

    public function slot() {
        return $this->belongsTo(InventorySlot::class, 'id', 'item_id');
    }

    public function scopeGetTotalDamage(): int {
        $damage = $this->base_damage;

        if (!is_null($this->artifactProperty)) {
            $damage += $this->base_damage_mod;
        }

        if ($this->itemAffixes->isNotEmpty()) {
            foreach ($this->itemAffixes as $affix) {
                $damage += $affix->base_damage_mod;
            }
        }

        return $damage;
    }
}
