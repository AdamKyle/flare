import React from 'react';

import MonsterSectionProps from '../types/partials/monster-section-props';

import { formatPercent } from 'game-utils/format-number';
import { isNilOrZeroValue } from 'game-utils/general-util';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';
import GeneralToolTip from 'ui/tool-tips/general-tool-tip';

const MonsterAmbushCounterSection = (props: MonsterSectionProps) => {
  const { monster } = props;

  const allZero =
    isNilOrZeroValue(monster.ambush_chance) &&
    isNilOrZeroValue(monster.ambush_resistance_chance) &&
    isNilOrZeroValue(monster.counter_chance) &&
    isNilOrZeroValue(monster.counter_resistance_chance);

  if (allZero) {
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

  const renderAmbushChance = () => {
    return (
      <>
        <Dt>
          {renderLabel(
            'Ambush Chance',
            'Ambush Chance',
            'At the start of combat, this is the chance that the monster ambushes you and acts before you. Certain gear affixes can reduce an enemy’s ambush chance.'
          )}
        </Dt>
        <Dd>{formatPercent(monster.ambush_chance)}</Dd>
      </>
    );
  };

  const renderAmbushResistance = () => {
    return (
      <>
        <Dt>
          {renderLabel(
            'Ambush Resistance',
            'Ambush Resistance',
            'The monster’s chance to resist being ambushed by players. Higher values make it harder for you to successfully ambush it.'
          )}
        </Dt>
        <Dd>{formatPercent(monster.ambush_resistance_chance)}</Dd>
      </>
    );
  };

  const renderCounterChance = () => {
    return (
      <>
        <Dt>
          {renderLabel(
            'Counter Chance',
            'Counter Chance',
            'When you strike the monster, this is the chance it immediately performs a counter-attack.'
          )}
        </Dt>
        <Dd>{formatPercent(monster.counter_chance)}</Dd>
      </>
    );
  };

  const renderCounterResistance = () => {
    return (
      <>
        <Dt>
          {renderLabel(
            'Counter Resistance',
            'Counter Resistance',
            'The monster’s chance to resist your counter effects, reducing the likelihood that your attacks trigger a counterable state.'
          )}
        </Dt>
        <Dd>{formatPercent(monster.counter_resistance_chance)}</Dd>
      </>
    );
  };

  return (
    <>
      <h3 className="text-danube-500 dark:text-danube-700 mt-5">
        Ambush and Counter
      </h3>
      <Separator />
      <Dl>
        {renderAmbushChance()}
        {renderAmbushResistance()}
        {renderCounterChance()}
        {renderCounterResistance()}
      </Dl>
    </>
  );
};

export default MonsterAmbushCounterSection;
