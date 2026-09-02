import React from 'react';

import MonsterSectionProps from '../types/partials/monster-section-props';

import {
  formatPercent,
  formatNumberWithCommas,
  formatRangeWithCommas,
} from 'game-utils/format-number';
import { isNilOrZeroValue } from 'game-utils/general-util';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';
import GeneralToolTip from 'ui/tool-tips/general-tool-tip';

const MonsterBasicStatsSection = ({ monster }: MonsterSectionProps) => {
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

  const renderHealthRange = () => {
    return (
      <>
        <Dt>{renderLabel('Health Range')}</Dt>
        <Dd>{formatRangeWithCommas(monster.health_range)}</Dd>
      </>
    );
  };

  const renderAttackRange = () => {
    return (
      <>
        <Dt>{renderLabel('Attack Range')}</Dt>
        <Dd>{formatRangeWithCommas(monster.attack_range)}</Dd>
      </>
    );
  };

  const renderIncreaseDamageBy = () => {
    if (isNilOrZeroValue(monster.increases_damage_by)) {
      return null;
    }

    return (
      <>
        <Dt>
          {renderLabel(
            'Increase Damage By',
            'Increase Damage By',
            'When the monster attacks, this percentage is applied to increase its overall damage.'
          )}
        </Dt>
        <Dd>{formatPercent(monster.increases_damage_by)}</Dd>
      </>
    );
  };

  const renderMaxSpellDamage = () => {
    if (isNilOrZeroValue(monster.spell_damage)) {
      return null;
    }

    return (
      <>
        <Dt>
          {renderLabel(
            'Max Spell Damage',
            'Max Spell Damage',
            'When the monster casts a damage spell, this is the maximum spell damage it can deal before applicable character mitigation.'
          )}
        </Dt>
        <Dd>{formatNumberWithCommas(monster.spell_damage)}</Dd>
      </>
    );
  };

  const renderMaxAffixDamage = () => {
    if (isNilOrZeroValue(monster.max_affix_damage)) {
      return null;
    }

    return (
      <>
        <Dt>
          {renderLabel(
            'Max Affix Damage',
            'Max Affix Damage',
            'This is the maximum affix damage the monster can deal before applicable character mitigation.'
          )}
        </Dt>
        <Dd>{formatNumberWithCommas(monster.max_affix_damage)}</Dd>
      </>
    );
  };

  const renderEntrancingChance = () => {
    if (isNilOrZeroValue(monster.entrancing_chance)) {
      return null;
    }

    return (
      <>
        <Dt>
          {renderLabel(
            'Entrancing Chance',
            'Entrancing Chance',
            "At the start of a fight with this monster, this is the chance it will entrance you for one turn, preventing you from attacking. Certain gear affixes reduce an enemy's chance to entrance you."
          )}
        </Dt>
        <Dd>{formatPercent(monster.entrancing_chance)}</Dd>
      </>
    );
  };

  const renderMaxHealing = () => {
    if (isNilOrZeroValue(monster.max_healing)) {
      return null;
    }

    return (
      <>
        <Dt>
          {renderLabel(
            'Max Healing',
            'Max Healing',
            "During the monster's attack phase, this percentage of its attack is converted into healing for the monster. Gear affixes can reduce how much an enemy heals during its attack phase."
          )}
        </Dt>
        <Dd>{formatPercent(monster.max_healing)}</Dd>
      </>
    );
  };

  const renderArmourClass = () => {
    if (isNilOrZeroValue(monster.ac)) {
      return null;
    }

    return (
      <>
        <Dt>{renderLabel('Armour Class (Defence)')}</Dt>
        <Dd>{formatNumberWithCommas(monster.ac)}</Dd>
      </>
    );
  };

  return (
    <>
      <h3 className="text-mango-tango-500 dark:text-mango-tango-300 mt-5">
        Basic Stats
      </h3>
      <Separator />

      <Dl>
        {renderHealthRange()}
        {renderAttackRange()}
        {renderIncreaseDamageBy()}
        {renderMaxSpellDamage()}
        {renderMaxAffixDamage()}
        {renderEntrancingChance()}
        {renderMaxHealing()}
        {renderArmourClass()}
      </Dl>
    </>
  );
};

export default MonsterBasicStatsSection;
