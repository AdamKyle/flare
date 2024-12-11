<?php

namespace App\Game\BattleRewardProcessing\Handlers;

use App\Flare\Models\User;
use App\Game\Messages\Types\CurrenciesMessageTypes;
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

        $message = 'You slaughtered: ' . number_format($numberOfCreatures) . ' and gained a totsl of: ' . number_format($totalXp);

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
}
