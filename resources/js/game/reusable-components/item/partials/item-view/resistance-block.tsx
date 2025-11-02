import React from 'react';

import { EquippableItemWithBase } from '../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import StatToolTip from '../../tool-tips/stat-tool-tip';
import {
  formatSignedPercent,
  getRingResistances,
  isRingType,
} from '../../utils/item-view';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';

const ResistanceBlock = ({ item }: { item: EquippableItemWithBase }) => {
  const ring = isRingType(item.type);

  if (!ring) {
    return null;
  }

  const { spellEvasion, affixDamageReduction, healingReduction } =
    getRingResistances(
      item as unknown as {
        spell_evasion?: number | null;
        affix_damage_reduction?: number | null;
        healing_reduction?: number | null;
      }
    );

  if (spellEvasion <= 0 && affixDamageReduction <= 0 && healingReduction <= 0) {
    return null;
  }

  const renderRow = (label: string, value: number, tip: string) => {
    if (!(value > 0)) {
      return null;
    }

    return (
      <>
        <Dt>
          <StatToolTip
            label={tip}
            value={value}
            renderAsPercent
            align="right"
            size="sm"
          />
          <span className="min-w-0 break-words">{label}</span>
        </Dt>
        <Dd>
          <span className="font-semibold text-emerald-600 dark:text-emerald-400">
            {formatSignedPercent(value)}
          </span>
        </Dd>
      </>
    );
  };

  return (
    <div>
      <h4 className="text-mango-tango-500 dark:text-mango-tango-300 mb-1 text-xs font-semibold tracking-wide uppercase">
        Resistances
      </h4>
      <Separator />
      <Dl>
        {renderRow(
          'Spell Evasion',
          spellEvasion,
          'raises your spell evasion to help you avoid the enemies magical attacks, having two rings equipped will stack the values'
        )}
        {renderRow(
          'Affix Damage Reduction',
          affixDamageReduction,
          'raises your affix damage reduction to help you take less affix damage from the enemy, having two rings will stack the values'
        )}
        {renderRow(
          'Healing Reduction',
          healingReduction,
          'raises your ability to reduce the amount the enemy can heal by'
        )}
      </Dl>
    </div>
  );
};

export default ResistanceBlock;
