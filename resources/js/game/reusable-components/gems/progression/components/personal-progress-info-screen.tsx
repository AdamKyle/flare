import React, { Fragment, ReactNode } from 'react';

import ProgressInfoScreenLayout from './progress-info-screen-layout';
import PersonalProgressInfoScreenProps from './types/personal-progress-info-screen-props';
import GemEffectRow from '../../components/gem-effect-row';
import { GemFieldProgressionBreakdownDefinition } from '../api/definitions/gem-progression-status-definition';
import { resolveGemProgressionFieldLabel } from '../utils/gem-progression-field-label';

import { formatPercent } from 'game-utils/format-number';

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
 * Gem profile: current effective effects, the negative-effect increase past
 * level 100, reward unlock bonuses, Gem Scroll eligibility, and next unlock.
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
    <ProgressInfoScreenLayout title="Personal Progress Info" on_close={onClose}>
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

      <p className="text-sm text-gray-700 dark:text-gray-300">
        Personal Progress applies only to your Character. It increases your
        effective positive Gem effects. After level 100 it also increases this
        Gem World&apos;s negative effects for your Character and unlocks Gem
        Scroll drop eligibility.
      </p>

      <Separator />

      <div>
        <h3 className="mb-1 text-sm font-semibold text-gray-800 dark:text-gray-200">
          Your Effective Gem Effects
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
                value={breakdown.effective}
              />
            ))}
          </div>
        )}
      </div>

      <Separator />

      <div>
        <h3 className="mb-1 text-sm font-semibold text-gray-800 dark:text-gray-200">
          Personal Negative Effect Increase
        </h3>
        <p className="text-sm text-gray-700 dark:text-gray-300">
          After Personal level 100, Personal Progress increases active negative
          Gem effects that apply to your Character.
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
              Personal Reward Unlock Bonuses
            </h3>
            <div className="flex flex-col">
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
    </ProgressInfoScreenLayout>
  );
};

export default PersonalProgressInfoScreen;
