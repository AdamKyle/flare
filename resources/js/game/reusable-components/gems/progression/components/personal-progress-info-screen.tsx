import React, { Fragment, ReactNode } from 'react';

import PersonalProgressInfoScreenProps from './types/personal-progress-info-screen-props';
import GemEffectRow from '../../components/gem-effect-row';
import { GemFieldProgressionBreakdownDefinition } from '../api/definitions/gem-progression-status-definition';
import { resolveGemProgressionFieldLabel } from '../utils/gem-progression-field-label';

import { formatPercent } from 'game-utils/format-number';

import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';
import Separator from 'ui/separator/separator';

interface PersonalBonusRow {
  field: string;
  label: string;
  value: number;
}

/**
 * Stacked detail screen for a Character's own Personal progression on one
 * Gem profile: positive additions, the negative-effect increase past level
 * 100, rarity/equipment unlocks, and the current Character-effective totals.
 */
const PersonalProgressInfoScreen = ({
  personal,
  scroll_drop: scrollDrop,
  reward_effect_breakdown: rewardEffectBreakdown,
  rarity_effect_breakdown: rarityEffectBreakdown,
  on_close: onClose,
}: PersonalProgressInfoScreenProps): ReactNode => {
  const combinedBreakdown: GemFieldProgressionBreakdownDefinition[] = [
    ...rewardEffectBreakdown,
    ...rarityEffectBreakdown,
  ];
  const atCap = personal.level >= personal.max_level;

  const positiveAdditions = combinedBreakdown.filter(
    (breakdown) => breakdown.personal > 0
  );

  const rarityRows: PersonalBonusRow[] = [
    {
      field: 'unique',
      label: 'Unique Drop Chance Bonus',
      value: personal.unique_chance_bonus,
    },
    {
      field: 'mythic',
      label: 'Mythic Drop Chance Bonus',
      value: personal.mythic_chance_bonus,
    },
    {
      field: 'cosmic',
      label: 'Cosmic Drop Chance Bonus',
      value: personal.cosmic_chance_bonus,
    },
    {
      field: 'enhanced_equipment_chance',
      label: 'Enhanced Equipment Chance',
      value: personal.enhanced_equipment_chance,
    },
  ].filter((row) => row.value > 0);

  return (
    <StackedCard
      on_close={onClose}
      content_mode={StackedCardContentMode.FULL_BLEED}
      aria_label="Personal Progress Info"
    >
      <div className="flex h-full flex-col gap-4 overflow-y-auto px-4 py-4 sm:px-5">
        <div>
          <ProgressBar
            label="Personal Level"
            value={personal.xp}
            max={Math.max(personal.next_level_xp, 1)}
            variant={ProgressBarVariant.PRIMARY}
            value_label={
              atCap
                ? `Level ${personal.level} (Max)`
                : `Level ${personal.level} — ${personal.xp}/${personal.next_level_xp} XP`
            }
          />
        </div>

        {positiveAdditions.length > 0 && (
          <Fragment>
            <Separator />
            <div>
              <h3 className="mb-1 text-sm font-semibold text-gray-800 dark:text-gray-200">
                Your Positive Gem Additions
              </h3>
              <div className="flex flex-col divide-y divide-gray-100 dark:divide-gray-800">
                {positiveAdditions.map((breakdown) => (
                  <GemEffectRow
                    key={breakdown.field}
                    field={breakdown.field}
                    label={resolveGemProgressionFieldLabel(breakdown.field)}
                    value={breakdown.personal}
                  />
                ))}
              </div>
            </div>
          </Fragment>
        )}

        <Separator />

        <div>
          <h3 className="mb-1 text-sm font-semibold text-gray-800 dark:text-gray-200">
            Personal Negative Effect Increase
          </h3>
          <p className="text-sm text-gray-700 dark:text-gray-300">
            Past level 100, your Personal progression also increases how
            difficult this Gem World&apos;s Monsters are for you specifically.
          </p>
          <GemEffectRow
            field="personal_negative_effect_increase"
            label="Current Increase"
            value={personal.negative_bonus}
          />
        </div>

        {rarityRows.length > 0 && (
          <Fragment>
            <Separator />
            <div>
              <h3 className="mb-1 text-sm font-semibold text-gray-800 dark:text-gray-200">
                Rarity and Equipment Progression
              </h3>
              <div className="flex flex-col divide-y divide-gray-100 dark:divide-gray-800">
                {rarityRows.map((row) => (
                  <GemEffectRow
                    key={row.field}
                    field={row.field}
                    label={row.label}
                    value={row.value}
                  />
                ))}
              </div>
            </div>
          </Fragment>
        )}

        <Separator />

        <div>
          <h3 className="mb-1 text-sm font-semibold text-gray-800 dark:text-gray-200">
            Gem Scroll Eligibility
          </h3>
          <p className="text-sm text-gray-700 dark:text-gray-300">
            {scrollDrop.eligible
              ? `You are eligible for Gem Scroll drops: ${formatPercent(scrollDrop.chance)} chance per qualifying kill.`
              : 'You are not yet eligible for Gem Scroll drops. Reach Personal level 100 for this profile to unlock them.'}
          </p>
        </div>

        {personal.next_unlock && (
          <Fragment>
            <Separator />
            <div>
              <h3 className="mb-1 text-sm font-semibold text-gray-800 dark:text-gray-200">
                Next Unlock
              </h3>
              <p className="text-sm text-gray-700 dark:text-gray-300">
                {personal.next_unlock.description} at level{' '}
                {personal.next_unlock.level}
              </p>
            </div>
          </Fragment>
        )}

        {combinedBreakdown.length > 0 && (
          <Fragment>
            <Separator />
            <div>
              <h3 className="mb-1 text-sm font-semibold text-gray-800 dark:text-gray-200">
                Effective For You
              </h3>
              <div className="flex flex-col divide-y divide-gray-100 dark:divide-gray-800">
                {combinedBreakdown.map((breakdown) => (
                  <Fragment key={breakdown.field}>
                    <GemEffectRow
                      field={breakdown.field}
                      label={resolveGemProgressionFieldLabel(breakdown.field)}
                      value={breakdown.effective}
                    />
                    <p className="pb-1 text-xs text-gray-500 dark:text-gray-400">
                      Base {formatPercent(breakdown.base)} + Global{' '}
                      {formatPercent(breakdown.global)} + Personal{' '}
                      {formatPercent(breakdown.personal)}
                    </p>
                  </Fragment>
                ))}
              </div>
            </div>
          </Fragment>
        )}
      </div>
    </StackedCard>
  );
};

export default PersonalProgressInfoScreen;
