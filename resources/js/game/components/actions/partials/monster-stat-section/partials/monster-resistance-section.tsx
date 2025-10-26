import React from 'react';

import MonsterSectionProps from '../types/partials/monster-section-props';

import { formatPercent } from 'game-utils/format-number';
import { isNilOrZeroValue } from 'game-utils/general-util';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';
import GeneralToolTip from 'ui/tool-tips/general-tool-tip';

const MonsterResistancesSection = (props: MonsterSectionProps) => {
  const { monster } = props;

  const affix = monster.affix_resistance;
  const spells = monster.spell_evasion;
  const lifeSteal = monster.life_stealing_resistance;

  const allZero =
    isNilOrZeroValue(affix) &&
    isNilOrZeroValue(spells) &&
    isNilOrZeroValue(lifeSteal);

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

  const renderAffix = () => {
    if (isNilOrZeroValue(affix)) {
      return null;
    }

    return (
      <>
        <Dt>
          {renderLabel(
            'Affix',
            'Affix Resistance',
            'The chance the monster resists your damage-dealing affixes. This applies only to affixes whose damage can be resisted. Affixes that reduce enemy resistances can lower this to 0%.'
          )}
        </Dt>
        <Dd>{formatPercent(affix)}</Dd>
      </>
    );
  };

  const renderSpells = () => {
    if (isNilOrZeroValue(spells)) {
      return null;
    }

    return (
      <>
        <Dt>
          {renderLabel(
            'Spells',
            'Spell Resistance',
            'The chance the monster resists your spell damage. Affixes that reduce enemy resistances can lower this to 0%.'
          )}
        </Dt>
        <Dd>{formatPercent(spells)}</Dd>
      </>
    );
  };

  const renderLifeSteal = () => {
    if (isNilOrZeroValue(lifeSteal)) {
      return null;
    }

    return (
      <>
        <Dt>
          {renderLabel(
            'Life Stealing',
            'Life-Steal Resistance',
            "The chance the monster resists a player's life-steal affixes and a Vampire's life-steal ability. Affixes that reduce enemy resistances can lower this to 0%."
          )}
        </Dt>
        <Dd>{formatPercent(lifeSteal)}</Dd>
      </>
    );
  };

  return (
    <>
      <h3 className="text-mango-tango-500 dark:text-mango-tango-300 mt-5">
        Resistances
      </h3>
      <Separator />
      <Dl>
        {renderAffix()}
        {renderSpells()}
        {renderLifeSteal()}
      </Dl>
    </>
  );
};

export default MonsterResistancesSection;
