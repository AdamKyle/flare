<?php

namespace App\Flare\Builders;

use App\User;
use App\Admin\Models\GameMap;
use App\Flare\Models\GameRace;
use App\Flare\Models\GameClass;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Values\BaseStatValue;
use App\Flare\Values\BaseSkillValue;
use App\Game\Maps\Values\MapPositionValue;
use Illuminate\Support\Facades\Storage;

class CharacterBuilder {

    private $race;

    private $class;

    private $character;

    public function setRace(GameRace $race): CharacterBuilder {
        $this->race = $race;

        return $this;
    }

    public function setClass(GameClass $class): CharacterBuilder {
        $this->class = $class;

        return $this;
    }

    public function createCharacter(User $user, GameMap $map, string $name): CharacterBuilder {
        $baseStat = resolve(BaseStatValue::class)->setRace($this->race)->setClass($this->class);

        $this->character = Character::create([
            'user_id'       => $user->id,
            'game_race_id'  => $this->race->id,
            'game_class_id' => $this->class->id,
            'name'          => $name,
            'damage_stat'   => $this->class->damage_stat,
            'xp'            => 0,
            'xp_next'       => 100,
            'str'           => $baseStat->str(),
            'dur'           => $baseStat->dur(),
            'dex'           => $baseStat->dex(),
            'chr'           => $baseStat->chr(),
            'int'           => $baseStat->int(),
            'ac'            => $baseStat->ac(),
        ]);

        $this->character->inventory()->create([
            'character_id' => $this->character->id
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id'      => Item::first()->id,
            'equipped'     => true,
            'position'     => 'left-hand',
        ]);

        $this->character->map()->create([
            'character_id' => $this->character->id,
            'game_map_id'  => $map->id,
        ]);

        return $this->assignPosition();
    }

    public function assignPosition(): CharacterBuilder {
        $xPosition = $this->getRandomY();
        $yPosition = $this->getRandomX();

        if ($this->isWater($this->character, $xPosition, $yPosition)) {
            return $this->assignPosition();
        }

        $mapPosition = resolve(MapPositionValue::class);

        $this->character->map->update([
            'character_position_x' => $xPosition,
            'character_position_y' => $yPosition,
            'position_x'           => $mapPosition->fetchXPosition($xPosition, 0),
            'position_x'           => $mapPosition->fetchYPosition($xPosition, 0),
        ]);

        return $this;
    }

    public function assignSkills(): CharacterBuilder {
        foreach (config('game.skills') as $options) {
            $this->character->skills()->create(
                resolve(BaseSkillValue::class)->getBaseCharacterSkillValue($this->character, $options)
            );
        }

        return $this;
    }

    public function character(): Character {
        return $this->character->refresh();
    }

    public function isWater(Character $character, int $x, int $y): bool {
        ini_set('memory_limit','12m');

        $contents            = Storage::disk('maps')->get($character->map->gameMap->path);
        $this->imageResource = imagecreatefromstring($contents);

        $waterRgb = 112219255;
        $rgb      = imagecolorat($this->imageResource, $x, $y);

        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        $color = $r.$g.$b;

        if ((int) $color === $waterRgb) {
            return true;
        }

        return false;
    }

    protected function getRandomY(): int {
        $randomY = rand(32, 1984);

        if ($randomY % 16 === 0) {
            return $randomY;
        }

        return $this->getRandomY();
    }

    protected function getRandomX(): int {
        $randomX = rand(16, 1984);

        if ($randomX % 16 === 0) {
            return $randomX;
        }

        return $this->getRandomX();
    }
}
