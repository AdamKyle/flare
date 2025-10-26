import React from 'react';

import MonsterSectionProps from '../types/partials/monster-section-props';

import { formatPercent } from 'game-utils/format-number';
import { isNilOrZeroValue } from 'game-utils/general-util';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';
import GeneralToolTip from 'ui/tool-tips/general-tool-tip';

const MonsterDevouringSection = (props: MonsterSectionProps) => {
  const { monster } = props;

  const light = monster.devouring_light_chance;
  const darkness = monster.devouring_darkness_chance;

  const allNilOrZero = isNilOrZeroValue(light) && isNilOrZeroValue(darkness);

  if (allNilOrZero) {
    return null;
  }

  const renderLabel = (
    label: string,
    tooltipLabel?: string,
    tooltipMessage?: string
  ) => {
    const hasTip =
      typeof tooltipLabel !== 'undefined' &&
      typeof tooltipMessage !== 'undefined';

    if (hasTip) {
      return (
        <div className="flex items-center gap-2">
          <span className="inline-flex w-4 shrink-0 justify-center">
            <GeneralToolTip label={tooltipLabel} message={tooltipMessage} />
          </span>
          <span className="whitespace-nowrap">{label}:</span>
        </div>
      );
    }

    return (
      <div className="flex items-center gap-2">
        <span className="inline-flex w-4 shrink-0 justify-center" />
        <span className="whitespace-nowrap">{label}:</span>
      </div>
    );
  };

  const renderDevouringLight = () => {
    if (isNilOrZeroValue(light)) {
      return null;
    }

    return (
      <>
        <Dt>
          {renderLabel(
            'Devouring Light',
            'Devouring Light',
            'At the start of a fight, this is the chance the monster voids you. While voided, all enchantments on your gear are disabled and their effects will not trigger. This can severely limit your damage and overall effectiveness, and it also impacts your stats during the battle. Certain quest items can increase your chance to void the enemy before they void you.'
          )}
        </Dt>
        <Dd>{formatPercent(light as number)}</Dd>
      </>
    );
  };

  const renderDevouringDarkness = () => {
    if (isNilOrZeroValue(darkness)) {
      return null;
    }

    return (
      <>
        <Dt>
          {renderLabel(
            'Devouring Darkness',
            'Devouring Darkness',
            'At the start of a fight, this is the chance the monster devoids you, preventing you from voiding them. In many encounters you will try to void the enemy first; however, on some maps (for example, Purgatory) monsters act first and can devoid you at the start of battle. Certain quest items can help you void the enemy before they can devoid you.'
          )}
        </Dt>
        <Dd>{formatPercent(darkness as number)}</Dd>
      </>
    );
  };

  return (
    <>
      <h3 className="text-mango-tango-500 dark:text-mango-tango-300 mt-5">
        Devouring Light / Darkness
      </h3>
      <Separator />
      <Dl>
        {renderDevouringLight()}
        {renderDevouringDarkness()}
      </Dl>
    </>
  );
};

export default MonsterDevouringSection;
