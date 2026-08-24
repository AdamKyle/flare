<?php

namespace App\Game\Battle\ServerFight\Fight\CharacterAttacks;

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
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\Values\SpecialAttackHandlerType;

class SpecialAttackRegistry
{
    public function __construct(
        private readonly HammerSmash $hammerSmash,
        private readonly AlchemistsRavenousDream $alchemistsRavenousDream,
        private readonly TripleAttack $tripleAttack,
        private readonly DoubleAttack $doubleAttack,
        private readonly DoubleCast $doubleCast,
        private readonly DoubleHeal $doubleHeal,
        private readonly VampireThirst $vampireThirst,
        private readonly PrisonerRage $prisonerRage,
        private readonly BloodyPuke $bloodyPuke,
        private readonly MerchantSupply $merchantSupply,
        private readonly GunslingersAssassination $gunslingersAssassination,
        private readonly SensualDance $sensualDance,
        private readonly BookBindersFear $bookBindersFear,
        private readonly HolySmite $holySmite,
        private readonly PlagueSurge $plagueSurge,
        private readonly BuccaneersBarrage $buccaneersBarrage,
        private readonly DevilsPiercingShot $devilsPiercingShot,
        private readonly BeastStomp $beastStomp,
        private readonly BuccaneersDualGunBarrage $buccaneersDualGunBarrage,
    ) {}

    /**
     * Return the closed-set handler instance for the given special attack type.
     */
    public function get(SpecialAttackHandlerType $type): object
    {
        return match ($type) {
            SpecialAttackHandlerType::HAMMER_SMASH => $this->hammerSmash,
            SpecialAttackHandlerType::ALCHEMISTS_RAVENOUS_DREAM => $this->alchemistsRavenousDream,
            SpecialAttackHandlerType::TRIPLE_ATTACK => $this->tripleAttack,
            SpecialAttackHandlerType::DOUBLE_ATTACK => $this->doubleAttack,
            SpecialAttackHandlerType::DOUBLE_CAST => $this->doubleCast,
            SpecialAttackHandlerType::DOUBLE_HEAL => $this->doubleHeal,
            SpecialAttackHandlerType::VAMPIRE_THIRST => $this->vampireThirst,
            SpecialAttackHandlerType::PRISONER_RAGE => $this->prisonerRage,
            SpecialAttackHandlerType::BLOODY_PUKE => $this->bloodyPuke,
            SpecialAttackHandlerType::MERCHANT_SUPPLY => $this->merchantSupply,
            SpecialAttackHandlerType::GUNSLINGERS_ASSASSINATION => $this->gunslingersAssassination,
            SpecialAttackHandlerType::SENSUAL_DANCE => $this->sensualDance,
            SpecialAttackHandlerType::BOOK_BINDERS_FEAR => $this->bookBindersFear,
            SpecialAttackHandlerType::HOLY_SMITE => $this->holySmite,
            SpecialAttackHandlerType::PLAGUE_SURGE => $this->plagueSurge,
            SpecialAttackHandlerType::BUCCANEERS_BARRAGE => $this->buccaneersBarrage,
            SpecialAttackHandlerType::DEVILS_PIERCING_SHOT => $this->devilsPiercingShot,
            SpecialAttackHandlerType::BEAST_STOMP => $this->beastStomp,
            SpecialAttackHandlerType::BUCCANEERS_DUAL_GUN_BARRAGE => $this->buccaneersDualGunBarrage,
        };
    }
}
