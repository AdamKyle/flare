import React from 'react';

import { EquippableItemWithBase } from '../../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import StatToolTip from '../../tool-tips/stat-tool-tip';
import {
  formatSignedInt,
  formatSignedPercent,
  getPrimaryLabelForType,
  getPrimaryValueForItem,
  isHealingSpellType,
  getResurrectionChance,
} from '../../utils/item-view';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';

const PrimaryStatBlock = ({ item }: { item: EquippableItemWithBase }) => {
  const primaryLabel = getPrimaryLabelForType(item.type);

  const primaryValue = getPrimaryValueForItem({
    type: item.type,
    raw_damage: item.raw_damage,
    raw_ac: item.raw_ac,
    raw_healing: item.raw_healing,
  });

  const getPrimaryInfoLabel = (label: 'Damage' | 'Healing' | 'AC') => {
    if (label === 'Damage') {
      return 'damage';
    }

    if (label === 'Healing') {
      return 'the amount you heal when casting';
    }

    return 'AC (Defence)';
  };

  const renderResurrectionSection = () => {
    if (!isHealingSpellType(item.type)) {
      return null;
    }

    const chance = getResurrectionChance(
      item as unknown as { resurrection_chance?: number | null }
    );

    return (
      <div className="mt-4">
        <h3 className="mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
          Resurrection Chance
        </h3>
        <Separator />
        <p className="my-2 text-sm text-gray-700 dark:text-gray-300">
          Raises the chance of you being resurrected when you are killed in
          battle. Having two healing spells will stack the chances.
        </p>
        <Dl>
          <Dt>Chance</Dt>
          <Dd>
            <span className="font-semibold text-emerald-600 dark:text-emerald-400">
              {formatSignedPercent(chance)}
            </span>
          </Dd>
        </Dl>
      </div>
    );
  };

  return (
    <div>
      <h3 className="mb-1 text-sm font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
        Damage / AC / Healing
      </h3>
      <Separator />
      <div className="mt-2 flex items-center gap-2">
        <StatToolTip
          label={getPrimaryInfoLabel(primaryLabel)}
          value={primaryValue}
          align="right"
          size="sm"
        />
        <span className="font-medium">{primaryLabel}:</span>
        <span className="font-semibold text-emerald-600 dark:text-emerald-400">
          {formatSignedInt(primaryValue)}
        </span>
      </div>

      {renderResurrectionSection()}
    </div>
  );
};

export default PrimaryStatBlock;
