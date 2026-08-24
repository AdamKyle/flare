<?php

namespace Tests\Setup\Battle\ServerFight;

use App\Flare\Models\Character;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks\AlchemistsRavenousDream;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks\BeastStomp;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks\BloodyPuke;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks\BookBindersFear;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks\BuccaneersBarrage;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks\BuccaneersDualGunBarrage;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks\DevilsPiercingShot;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks\DoubleAttack;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks\DoubleCast;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks\DoubleHeal;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks\GunslingersAssassination;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks\HammerSmash;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks\HolySmite;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks\MerchantSupply;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks\PlagueSurge;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks\PrisonerRage;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks\SensualDance;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks\TripleAttack;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks\VampireThirst;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use Mockery;
use Tests\Setup\Character\CharacterFactory;

class SpecialAttackFactory
{
    public function buildCharacter(string $className, array $classOptions = []): Character
    {
        return (new CharacterFactory)
            ->createBaseCharacter([], array_merge(['name' => $className], $classOptions), assignPassiveSkills: false)
            ->givePlayerLocation()
            ->getCharacter();
    }

    public function buildAlchemistsRavenousDream(?RandomNumberGenerator $randomNumberGenerator = null): AlchemistsRavenousDream
    {
        return new AlchemistsRavenousDream(...$this->collaborators($randomNumberGenerator));
    }

    public function buildBloodyPuke(?RandomNumberGenerator $randomNumberGenerator = null): BloodyPuke
    {
        return new BloodyPuke(...$this->collaborators($randomNumberGenerator));
    }

    public function buildBookBindersFear(?RandomNumberGenerator $randomNumberGenerator = null): BookBindersFear
    {
        return new BookBindersFear(...$this->collaborators($randomNumberGenerator));
    }

    public function buildDoubleAttack(?RandomNumberGenerator $randomNumberGenerator = null): DoubleAttack
    {
        return new DoubleAttack(...$this->collaborators($randomNumberGenerator));
    }

    public function buildDoubleCast(?RandomNumberGenerator $randomNumberGenerator = null): DoubleCast
    {
        return new DoubleCast(...$this->collaborators($randomNumberGenerator));
    }

    public function buildDoubleHeal(?RandomNumberGenerator $randomNumberGenerator = null): DoubleHeal
    {
        return new DoubleHeal(...$this->collaborators($randomNumberGenerator));
    }

    public function buildGunslingersAssassination(?RandomNumberGenerator $randomNumberGenerator = null): GunslingersAssassination
    {
        return new GunslingersAssassination(...$this->collaborators($randomNumberGenerator));
    }

    public function buildHammerSmash(?RandomNumberGenerator $randomNumberGenerator = null): HammerSmash
    {
        return new HammerSmash(...$this->collaborators($randomNumberGenerator));
    }

    public function buildHolySmite(?RandomNumberGenerator $randomNumberGenerator = null): HolySmite
    {
        return new HolySmite(...$this->collaborators($randomNumberGenerator));
    }

    public function buildMerchantSupply(?RandomNumberGenerator $randomNumberGenerator = null): MerchantSupply
    {
        return new MerchantSupply(...$this->collaborators($randomNumberGenerator));
    }

    public function buildPlagueSurge(?RandomNumberGenerator $randomNumberGenerator = null): PlagueSurge
    {
        return new PlagueSurge(...$this->collaborators($randomNumberGenerator));
    }

    public function buildPrisonerRage(?RandomNumberGenerator $randomNumberGenerator = null): PrisonerRage
    {
        return new PrisonerRage(...$this->collaborators($randomNumberGenerator));
    }

    public function buildSensualDance(?RandomNumberGenerator $randomNumberGenerator = null): SensualDance
    {
        return new SensualDance(...$this->collaborators($randomNumberGenerator));
    }

    public function buildTripleAttack(?RandomNumberGenerator $randomNumberGenerator = null): TripleAttack
    {
        return new TripleAttack(...$this->collaborators($randomNumberGenerator));
    }

    public function buildVampireThirst(?RandomNumberGenerator $randomNumberGenerator = null): VampireThirst
    {
        return new VampireThirst(...$this->collaborators($randomNumberGenerator));
    }

    public function buildBeastStomp(?RandomNumberGenerator $randomNumberGenerator = null): BeastStomp
    {
        return new BeastStomp(...$this->collaborators($randomNumberGenerator));
    }

    public function buildBuccaneersBarrage(?RandomNumberGenerator $randomNumberGenerator = null): BuccaneersBarrage
    {
        return new BuccaneersBarrage(...$this->collaborators($randomNumberGenerator));
    }

    public function buildBuccaneersDualGunBarrage(?RandomNumberGenerator $randomNumberGenerator = null): BuccaneersDualGunBarrage
    {
        return new BuccaneersDualGunBarrage(...$this->collaborators($randomNumberGenerator));
    }

    public function buildDevilsPiercingShot(?RandomNumberGenerator $randomNumberGenerator = null): DevilsPiercingShot
    {
        return new DevilsPiercingShot(...$this->collaborators($randomNumberGenerator));
    }

    private function collaborators(?RandomNumberGenerator $randomNumberGenerator): array
    {
        $randomNumberGenerator ??= Mockery::mock(RandomNumberGenerator::class);

        return [
            (new CharacterCacheDataFactory())->build(),
            new ChanceCalculator($randomNumberGenerator),
            $randomNumberGenerator,
        ];
    }
}
