<?php

namespace App\Admin\Services;

use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Models\GameMapGemParamter;
use App\Flare\Models\Gem;
use App\Flare\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AdminGemRollService
{
    public function rollMapGem(GameMapGemParamter $gameMapGemParamter, User $admin): Gem
    {
        return $this->roll(
            $gameMapGemParamter,
            $admin,
            Gem::DOMAIN_MAP,
            'game_map_gem_paramters_id',
        );
    }

    public function rollLocationGem(GameLocationGemParamter $gameLocationGemParamter, User $admin): Gem
    {
        return $this->roll(
            $gameLocationGemParamter,
            $admin,
            Gem::DOMAIN_LOCATION,
            'game_location_gem_paramters_id',
        );
    }

    private function roll(
        GameMapGemParamter|GameLocationGemParamter $profile,
        User $admin,
        string $domain,
        string $sourceForeignKey,
    ): Gem {
        return DB::transaction(function () use (
            $profile,
            $admin,
            $domain,
            $sourceForeignKey,
        ): Gem {
            $lockedProfile = $profile->newQuery()
                ->lockForUpdate()
                ->findOrFail($profile->getKey());
            $rollNumber = $lockedProfile->roll_count + 1;
            $gemData = [
                'name' => $lockedProfile->name,
                'domain' => $domain,
                'rolled_by_user_id' => $admin->id,
                'roll_number' => $rollNumber,
                $sourceForeignKey => $lockedProfile->id,
                'crafting_skill_ids' => $lockedProfile->crafting_skill_ids,
                'monster_atonement' => $lockedProfile->monster_atonement,
                'monster_atonement_amount' => $this->rollRange($lockedProfile->monster_atonement_range),
            ];

            foreach ($lockedProfile->rollableRangeFields() as $rangeField) {
                $gemData[str($rangeField)->beforeLast('_range')->toString()] = $this->rollRange(
                    $lockedProfile->{$rangeField},
                );
            }

            $gem = Gem::create($gemData);

            $lockedProfile->update([
                'rolled_gem_id' => $gem->id,
                'roll_count' => $rollNumber,
            ]);

            return $gem;
        });
    }

    private function rollRange(?string $range): ?float
    {
        if (is_null($range) || trim($range) === '') {
            return null;
        }

        $rangeValues = explode('-', $range, 2);

        if (count($rangeValues) !== 2) {
            throw new InvalidArgumentException('Invalid gem roll range: '.$range);
        }

        $firstValue = trim($rangeValues[0]);
        $secondValue = trim($rangeValues[1]);

        if (! is_numeric($firstValue) || ! is_numeric($secondValue)) {
            throw new InvalidArgumentException('Invalid gem roll range: '.$range);
        }

        $lower = min($firstValue, $secondValue);
        $upper = max($firstValue, $secondValue);
        $percentage = mt_rand() / mt_getrandmax();

        return round($lower + (($upper - $lower) * $percentage), 8);
    }
}
