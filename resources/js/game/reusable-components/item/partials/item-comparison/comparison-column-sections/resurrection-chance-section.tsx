import React, { Fragment } from 'react';

import { InventoryItemTypes } from '../../../../../components/character-sheet/partials/character-inventory/enums/inventory-item-types';
import StatInfoToolTip from '../../../stat-info-tool-tip';
import ResurrectionChanceSectionProps from '../../../types/partials/item-comparison/comparison-column-sections/resurrection-chance-section-props';
import AdjustmentChangeDisplay from '../adjustment-change-display';

import Separator from 'ui/separator/separator';

const ResurrectionChanceSection = ({
  adjustments,
  toEquipType,
}: ResurrectionChanceSectionProps) => {
  if (toEquipType !== InventoryItemTypes.SPELL_HEALING) {
    return null;
  }

  const raw = adjustments.resurrection_chance_adjustment;

  if (raw == null || Number(raw) === 0) {
    return null;
  }

  const value = Number(raw);

  return (
    <Fragment>
      <h4 className="mt-3 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
        Resurrection Chance
      </h4>
      <Separator />
      <dl className="grid grid-cols-[1fr_auto] items-center gap-x-4 gap-y-1">
        <dt className="font-medium text-gray-900 dark:text-gray-100">
          <div className="flex items-center">
            <StatInfoToolTip
              label="chance"
              value={value}
              renderAsPercent
              align="left"
              size="sm"
              custom_message
            />
            <span className="min-w-0 break-words">Chance</span>
          </div>
        </dt>
        <dd>
          <AdjustmentChangeDisplay
            value={value}
            label="Chance"
            renderAsPercent
          />
        </dd>
      </dl>
      <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
        <em>
          This will increase or decrease your chance by the shown amount to be
          resurrected on death. Having two healing spells stacks the chance.
        </em>
      </p>
    </Fragment>
  );
};

export default ResurrectionChanceSection;
