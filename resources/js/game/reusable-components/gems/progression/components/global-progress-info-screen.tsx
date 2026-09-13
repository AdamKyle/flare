import React, { Fragment, ReactNode } from 'react';

import GlobalProgressInfoScreenProps from './types/global-progress-info-screen-props';
import GemEffectRow from '../../components/gem-effect-row';
import { GemFieldProgressionBreakdownDefinition } from '../api/definitions/gem-progression-status-definition';
import { resolveGemProgressionFieldLabel } from '../utils/gem-progression-field-label';

import { formatPercent } from 'game-utils/format-number';

import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
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
  scroll_drop: scrollDrop,
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
    <StackedCard
      on_close={onClose}
      content_mode={StackedCardContentMode.FULL_BLEED}
      aria_label="Global Progress Info"
    >
      <div className="flex h-full flex-col gap-4 overflow-y-auto px-4 py-4 sm:px-5">
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
          Global progression is shared by every Character who benefits from this
          Gem profile. Every qualifying kill any Character makes here
          contributes Global XP, and the improvement below applies for everyone.
        </p>

        <Separator />

        <div>
          <h3 className="mb-1 text-sm font-semibold text-gray-800 dark:text-gray-200">
            Base Gem &amp; Global Improvement
          </h3>
          {combinedBreakdown.length === 0 ? (
            <p className="text-sm text-gray-600 dark:text-gray-400">
              No positive Gem effects currently roll for this profile.
            </p>
          ) : (
            <div className="flex flex-col divide-y divide-gray-100 dark:divide-gray-800">
              {combinedBreakdown.map((breakdown) => (
                <Fragment key={breakdown.field}>
                  <GemEffectRow
                    field={breakdown.field}
                    label={`${resolveGemProgressionFieldLabel(breakdown.field)} (Global Effective)`}
                    value={breakdown.global_effective}
                  />
                  <p className="pb-1 text-xs text-gray-500 dark:text-gray-400">
                    Base {formatPercent(breakdown.base)} + Global{' '}
                    {formatPercent(breakdown.global)}
                  </p>
                </Fragment>
              ))}
            </div>
          )}
        </div>

        <Separator />

        <div>
          <h3 className="mb-1 text-sm font-semibold text-gray-800 dark:text-gray-200">
            Gem Scroll Drops
          </h3>
          <p className="text-sm text-gray-700 dark:text-gray-300">
            Once a Character reaches Personal level 100 for this profile,
            qualifying kills anywhere in this Gem World have a{' '}
            {formatPercent(scrollDrop.chance)} chance to drop a Gem Scroll.
          </p>
        </div>
      </div>
    </StackedCard>
  );
};

export default GlobalProgressInfoScreen;
