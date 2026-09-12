import React, { Fragment, ReactNode } from 'react';

import GemProgressionLevelSectionProps from './types/gem-progression-level-section-props';
import { GemFieldProgressionBreakdownDefinition } from '../api/definitions/gem-progression-status-definition';
import { resolveGemProgressionFieldLabel } from '../utils/gem-progression-field-label';

import { formatPercent } from 'game-utils/format-number';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import ProgressBar from 'ui/progress/progress-bar';
import Separator from 'ui/separator/separator';

interface PersonalBonusRow {
  label: string;
  value: number;
}

const POSITIVE_VALUE_CLASS = 'text-emerald-600 dark:text-emerald-400';

const GemProgressionLevelSection = ({
  global,
  personal,
  scroll_drop: scrollDrop,
  reward_effect_breakdown: rewardEffectBreakdown,
  rarity_effect_breakdown: rarityEffectBreakdown,
}: GemProgressionLevelSectionProps): ReactNode => {
  const globalAtCap = global.level >= global.max_level;
  const personalAtCap = personal.level >= personal.max_level;
  const combinedBreakdown: GemFieldProgressionBreakdownDefinition[] = [
    ...rewardEffectBreakdown,
    ...rarityEffectBreakdown,
  ];

  const personalBonusRows: PersonalBonusRow[] = [
    { label: 'Unique Drop Chance Bonus', value: personal.unique_chance_bonus },
    { label: 'Mythic Drop Chance Bonus', value: personal.mythic_chance_bonus },
    { label: 'Cosmic Drop Chance Bonus', value: personal.cosmic_chance_bonus },
    {
      label: 'Enhanced Equipment Chance',
      value: personal.enhanced_equipment_chance,
    },
  ].filter((row) => row.value > 0);

  const renderBaseGlobalImprovement = (): ReactNode => {
    if (combinedBreakdown.length === 0) {
      return (
        <div className="text-sm text-gray-600 dark:text-gray-400">
          No positive Gem effects currently roll for this profile.
        </div>
      );
    }

    return (
      <Dl>
        {combinedBreakdown.map((breakdown) => (
          <Fragment key={breakdown.field}>
            <Dt>{resolveGemProgressionFieldLabel(breakdown.field)}</Dt>
            <Dd>
              <span className={POSITIVE_VALUE_CLASS}>
                {formatPercent(breakdown.global_effective)}
              </span>
              <div className="text-xs text-gray-500 dark:text-gray-400">
                Base {formatPercent(breakdown.base)} + Global{' '}
                {formatPercent(breakdown.global)}
              </div>
            </Dd>
          </Fragment>
        ))}
      </Dl>
    );
  };

  const renderPersonalPositiveAdditions = (): ReactNode => {
    const applicable = combinedBreakdown.filter(
      (breakdown) => breakdown.personal > 0
    );

    if (applicable.length === 0) {
      return null;
    }

    return (
      <div>
        <h4 className="text-glacier-800 dark:text-glacier-200 mb-1 text-xs font-semibold tracking-wide uppercase">
          Your Positive Gem Additions
        </h4>
        <Dl>
          {applicable.map((breakdown) => (
            <Fragment key={breakdown.field}>
              <Dt>{resolveGemProgressionFieldLabel(breakdown.field)}</Dt>
              <Dd>
                <span className={POSITIVE_VALUE_CLASS}>
                  +{formatPercent(breakdown.personal)}
                </span>
              </Dd>
            </Fragment>
          ))}
        </Dl>
      </div>
    );
  };

  const renderEffectiveTotals = (): ReactNode => {
    if (combinedBreakdown.length === 0) {
      return null;
    }

    return (
      <div>
        <h4 className="text-glacier-800 dark:text-glacier-200 mb-1 text-xs font-semibold tracking-wide uppercase">
          Effective For You
        </h4>
        <Dl>
          {combinedBreakdown.map((breakdown) => (
            <Fragment key={breakdown.field}>
              <Dt>{resolveGemProgressionFieldLabel(breakdown.field)}</Dt>
              <Dd>
                <span className={POSITIVE_VALUE_CLASS}>
                  {formatPercent(breakdown.effective)}
                </span>
                <div className="text-xs text-gray-500 dark:text-gray-400">
                  Base {formatPercent(breakdown.base)} + Global{' '}
                  {formatPercent(breakdown.global)} + Personal{' '}
                  {formatPercent(breakdown.personal)}
                </div>
              </Dd>
            </Fragment>
          ))}
        </Dl>
      </div>
    );
  };

  const renderRarityAndEquipmentProgression = (): ReactNode => {
    if (personalBonusRows.length === 0) {
      return null;
    }

    return (
      <div>
        <h4 className="text-glacier-800 dark:text-glacier-200 mb-1 text-xs font-semibold tracking-wide uppercase">
          Rarity and Equipment Progression
        </h4>
        <Dl>
          {personalBonusRows.map((row) => (
            <Fragment key={row.label}>
              <Dt>{row.label}</Dt>
              <Dd>
                <span className={POSITIVE_VALUE_CLASS}>
                  {formatPercent(row.value)}
                </span>
              </Dd>
            </Fragment>
          ))}
        </Dl>
      </div>
    );
  };

  const renderNextUnlock = (): ReactNode => {
    if (!personal.next_unlock) {
      return null;
    }

    return (
      <Fragment>
        <Dt>Next Unlock</Dt>
        <Dd>
          {personal.next_unlock.description} at level{' '}
          {personal.next_unlock.level}
        </Dd>
      </Fragment>
    );
  };

  return (
    <div className="flex flex-col gap-4">
      <div>
        <ProgressBar
          label="Global Level"
          value={global.xp}
          max={Math.max(global.next_level_xp, 1)}
          variant={ProgressBarVariant.PRIMARY}
          value_label={
            globalAtCap
              ? `Level ${global.level} (Max)`
              : `Level ${global.level} — ${global.xp}/${global.next_level_xp} XP`
          }
        />
      </div>

      <Separator />

      <div>
        <h4 className="text-glacier-800 dark:text-glacier-200 mb-1 text-xs font-semibold tracking-wide uppercase">
          Base Gem & Global Improvement
        </h4>
        {renderBaseGlobalImprovement()}
      </div>

      <Separator />

      <div>
        <ProgressBar
          label="Personal Level"
          value={personal.xp}
          max={Math.max(personal.next_level_xp, 1)}
          variant={ProgressBarVariant.PRIMARY}
          value_label={
            personalAtCap
              ? `Level ${personal.level} (Max)`
              : `Level ${personal.level} — ${personal.xp}/${personal.next_level_xp} XP`
          }
        />
      </div>

      {renderPersonalPositiveAdditions()}

      <Dl>
        <Dt>Personal Negative Effect Increase</Dt>
        <Dd>{formatPercent(personal.negative_bonus)}</Dd>
      </Dl>

      {renderRarityAndEquipmentProgression()}

      <div>
        <h4 className="text-glacier-800 dark:text-glacier-200 mb-1 text-xs font-semibold tracking-wide uppercase">
          Gem Scroll Drops
        </h4>
        <Dl>
          <Dt>Eligible</Dt>
          <Dd>{scrollDrop.eligible ? 'Yes' : 'Not yet'}</Dd>
          <Dt>Chance Per Qualifying Kill</Dt>
          <Dd>{formatPercent(scrollDrop.chance)}</Dd>
          {renderNextUnlock()}
        </Dl>
      </div>

      <Separator />

      {renderEffectiveTotals()}
    </div>
  );
};

export default GemProgressionLevelSection;
