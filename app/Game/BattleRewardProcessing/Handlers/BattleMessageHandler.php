<?php

namespace App\Game\BattleRewardProcessing\Handlers;

use App\Flare\Models\User;
use App\Game\Messages\Types\ClassRanksMessageTypes;
use App\Game\Messages\Types\CurrenciesMessageTypes;
use Doctrine\Common\Cache\Psr6\InvalidArgument;
use Facades\App\Flare\Values\UserOnlineValue;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class BattleMessageHandler
{

    /**
     * Handle message for exploration xp
     *
     * - Only show if we are online.
     * - Only show if we have the setting enabled.
     *
     * @param User $user
     * @param integer $numberOfCreatures
     * @param integer $totalXp
     * @return void
     */
    public function handleMessageForExplorationXp(User $user, int $numberOfCreatures, int $totalXp): void
    {
        if (!UserOnlineValue::isOnline($user)) {
            return;
        }

        if (!$user->show_xp_for_exploration) {
            return;
        }

        $message = 'You slaughtered: ' . number_format($numberOfCreatures) . ' and gained a total of: ' . number_format($totalXp);

        ServerMessageHandler::sendBasicMessage($user, $message);
    }

    /**
     * Handle message for gaining xp for faction loyalty
     *
     * @param User $user
     * @param integer $totalXp
     * @param integer $newFameLevel
     * @param string $npcName
     * @return void
     */
    public function handleFactionLoyaltyXp(User $user, int $totalXp, int $newFameLevel, string $npcName): void
    {
        if (!UserOnlineValue::isOnline($user)) {
            return;
        }

        if (!$user->show_xp_per_kill) {
            return;
        }

        $message = 'For gaining a new fame level (' . $newFameLevel . ') for helping: ' . $npcName . ' with their tasks you were rewarded with: ' . number_format($totalXp) . ' XP!';

        ServerMessageHandler::sendBasicMessage($user, $message);
    }

    /**
     * Handle sending out messages for faction point gains.
     *
     * @param User $user
     * @param integer $numberOfPointsToGain
     * @param integer $currentPoints
     * @param integer $maxPointsNeeded
     * @return void
     */
    public function handleFactionPointGain(User $user, int $numberOfPointsToGain, int $currentPoints, int $maxPointsNeeded): void
    {
        if (!UserOnlineValue::isOnline($user)) {
            return;
        }

        if (!$user->show_faction_point_message) {
            return;
        }

        $neededPoints = $maxPointsNeeded - $currentPoints;

        $message = 'You gained: ' . number_format($numberOfPointsToGain) . ' Faction Points, which puts you at: ' . number_format($currentPoints) . '. You neex ' . number_format($neededPoints) . ' more points to gain a new level!';

        ServerMessageHandler::sendBasicMessage($user, $message);
    }

    /**
     * Handle the currency gain message.
     *
     * - Only show if the user is online
     * - Only show if the relevant setting is enabled:
     *   - show_gold_per_kill (for gold, excludes: gold rushes)
     *   - show_gold_dust_per_kill (for gold dust)
     *   - show_shards_per_kill (for shards)
     *   - show_copper_coins_per_kill (for copper coins)
     *
     * @param User $user
     * @param CurrenciesMessageTypes $currencyType
     * @param integer $currencyGain
     * @param integer $newTotal
     * @return void
     */
    public function handleCurrencyGainMessage(User $user, CurrenciesMessageTypes $currencyType, int $currencyGain, int $newTotal): void
    {

        if (!UserOnlineValue::isOnline($user)) {
            return;
        }

        $shouldShowMessage = match ($currencyType) {
            CurrenciesMessageTypes::GOLD => $user->show_gold_per_kill,
            CurrenciesMessageTypes::GOLD_DUST => $user->show_gold_dust_per_kill,
            CurrenciesMessageTypes::SHARDS => $user->show_shards_per_kill,
            CurrenciesMessageTypes::COPPER_COINS => $user->show_copper_coins_per_kill,
            default => false,
        };

        if (!$shouldShowMessage) {
            return;
        }

        ServerMessageHandler::handleMessageWithNewValue(
            $user,
            $currencyType,
            $currencyGain,
            $newTotal,
        );
    }

    /**
     * Uhandles messages for class ranks when they gain XP.
     *
     * This includes:
     *   - Class Masteries (wespon masteries)
     *   - Class Ranks
     *   - Class Specials
     *
     * @param User $user
     * @param ClassRanksMessageTypes $classRanksMessageTypes
     * @param string $className
     * @param integer $xpGiven
     * @param integer $currentXp
     * @param string|null $weaponMastery
     * @param string|null $classspecial
     * @throws InvalidArgumentException
     * @return void
     */
    public function handleClassRankMessage(User $user, ClassRanksMessageTypes $classRanksMessageTypes, string $className, int $xpGiven, int $currentXp, string $weaponMastery = null, string $classspecial = null): void
    {
        if (!UserOnlineValue::isOnline($user)) {
            return;
        }

        $shouldShowMessage = match ($classRanksMessageTypes) {
            ClassRanksMessageTypes::XP_FOR_CLASS_MASTERIES => $user->show_xp_for_class_masteries,
            ClassRanksMessageTypes::XP_FOR_CLASS_RANKS => $user->show_xp_for_class_ranks,
            ClassRanksMessageTypes::XP_FOR_EQUIPPED_CLASS_SPECIALS => $user->show_xp_for_equipped_class_specials,
            default => false,
        };

        if (!$shouldShowMessage) {
            return;
        }

        $message = match ($classRanksMessageTypes) {
            ClassRanksMessageTypes::XP_FOR_CLASS_MASTERIES => 'Your class: ' . $className . ' has gained experience in a weapon mastery: ' . $weaponMastery . ' of: ' . number_format($xpGiven) . ' XP and now has a total of: ' . number_format($currentXp) . ' XP.',
            ClassRanksMessageTypes::XP_FOR_CLASS_RANKS => 'Your class rank: ' . $className . ' has gained experience of: ' . number_format($xpGiven) . ' XP and now has a total of: ' . number_format($currentXp) . ' XP.',
            ClassRanksMessageTypes::XP_FOR_EQUIPPED_CLASS_SPECIALS => 'Your class rank: ' . $className . ' has gained experience in a specialty ypu have equipped: ' . $classspecial . ' of: ' . number_format($xpGiven) . ' XP and now has a total of: ' . number_format($currentXp) . ' XP.',
            default => throw new InvalidArgument('Invalid message type was given ...'),
        };

        ServerMessageHandler::sendBasicMessage($user, $message);
    }
}
