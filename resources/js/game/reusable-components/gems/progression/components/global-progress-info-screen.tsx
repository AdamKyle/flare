import React, { ReactNode } from 'react';

import ProgressInfoScreenLayout from './progress-info-screen-layout';
import GlobalProgressInfoScreenProps from './types/global-progress-info-screen-props';
import GemEffectRow from '../../components/gem-effect-row';
import { GemFieldProgressionBreakdownDefinition } from '../api/definitions/gem-progression-status-definition';
import { resolveGemProgressionFieldLabel } from '../utils/gem-progression-field-label';

import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';
import Separator from 'ui/separator/separator';

/**
 * Stacked detail screen for a Gem profile's shared Global progression:
 * everything that improves for every Character benefiting from this profile,
 * not just the viewing Character.
 */
const GlobalProgressInfoScreen = ({
  global,
  reward_effect_breakdown: rewardEffectBreakdown,
  rarity_effect_breakdown: rarityEffectBreakdown,
  on_close: onClose,
}: GlobalProgressInfoScreenProps): ReactNode => {
  const combinedBreakdown: GemFieldProgressionBreakdownDefinition[] = [
    ...rewardEffectBreakdown,
    ...rarityEffectBreakdown,
  ];
  const atCap = global.level >= global.max_level;

  return (
    <ProgressInfoScreenLayout title="Global Progress Info" on_close={onClose}>
      <div>
        <ProgressBar
          label="Global Level"
          value={global.xp}
          max={Math.max(global.next_level_xp, 1)}
          variant={ProgressBarVariant.PRIMARY}
          value_label={
            atCap
              ? `Level ${global.level} (Max)`
              : `Level ${global.level} — ${global.xp}/${global.next_level_xp} XP`
          }
        />
      </div>

      <p className="text-sm text-gray-700 dark:text-gray-300">
        Every qualifying kill in this Gem World adds Global XP. Global levels
        increase this Gem profile&apos;s positive effects for every Character
        who benefits from it. The values below are the current shared effects
        after the Global bonus is applied.
      </p>

      <Separator />

      <div>
        <h3 className="mb-1 text-sm font-semibold text-gray-800 dark:text-gray-200">
          Shared Gem Effects
        </h3>
        {combinedBreakdown.length === 0 ? (
          <p className="text-sm text-gray-600 dark:text-gray-400">
            No positive Gem effects currently roll for this profile.
          </p>
        ) : (
          <div className="flex flex-col">
            {combinedBreakdown.map((breakdown) => (
              <GemEffectRow
                key={breakdown.field}
                field={breakdown.field}
                label={resolveGemProgressionFieldLabel(breakdown.field)}
                value={breakdown.global_effective}
              />
            ))}
          </div>
        )}
      </div>
    </ProgressInfoScreenLayout>
  );
};

export default GlobalProgressInfoScreen;
