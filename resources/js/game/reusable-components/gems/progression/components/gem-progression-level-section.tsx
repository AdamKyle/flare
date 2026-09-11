import React, { Fragment, ReactNode } from 'react';

import GemProgressionLevelSectionProps from './types/gem-progression-level-section-props';

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

const GemProgressionLevelSection = ({
  global,
  personal,
  scroll_drop: scrollDrop,
}: GemProgressionLevelSectionProps): ReactNode => {
  const globalAtCap = global.level >= global.max_level;
  const personalAtCap = personal.level >= personal.max_level;

  const personalBonusRows: PersonalBonusRow[] = [
    {
      label: 'Personal Enemy/Negative Effect Increase',
      value: personal.negative_bonus,
    },
    { label: 'Unique Drop Chance Bonus', value: personal.unique_chance_bonus },
    { label: 'Mythic Drop Chance Bonus', value: personal.mythic_chance_bonus },
    { label: 'Cosmic Drop Chance Bonus', value: personal.cosmic_chance_bonus },
    {
      label: 'Enhanced Equipment Chance',
      value: personal.enhanced_equipment_chance,
    },
  ].filter((row) => row.value > 0);

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

      {personalBonusRows.length > 0 && (
        <Fragment>
          <Separator />
          <div>
            <h4 className="text-glacier-800 dark:text-glacier-200 mb-1 text-xs font-semibold tracking-wide uppercase">
              Personal Bonuses
            </h4>
            <Dl>
              {personalBonusRows.map((row) => (
                <Fragment key={row.label}>
                  <Dt>{row.label}</Dt>
                  <Dd>{formatPercent(row.value)}</Dd>
                </Fragment>
              ))}
            </Dl>
          </div>
        </Fragment>
      )}

      <Separator />
      <div>
        <h4 className="text-glacier-800 dark:text-glacier-200 mb-1 text-xs font-semibold tracking-wide uppercase">
          Gem Scroll Drops
        </h4>
        <Dl>
          <Dt>Eligible</Dt>
          <Dd>{scrollDrop.eligible ? 'Yes' : 'Not yet'}</Dd>
          <Dt>Chance Per Qualifying Kill</Dt>
          <Dd>{formatPercent(scrollDrop.chance)}</Dd>
        </Dl>
      </div>
    </div>
  );
};

export default GemProgressionLevelSection;
