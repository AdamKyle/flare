import React, { Fragment } from 'react';

import { InventoryItemTypes } from '../../../../../components/character-sheet/partials/character-inventory/enums/inventory-item-types';
import { RESISTANCE_FIELDS } from '../../../constants/item-comparison-constants';
import StatInfoToolTip from '../../../stat-info-tool-tip';
import ResistanceSectionProps from '../../../types/partials/item-comparison/comparison-column-sections/resistance-section-props';
import { hasAnyNonZeroAdjustment } from '../../../utils/item-comparison';
import AdjustmentChangeDisplay from '../adjustment-change-display';

import Separator from 'ui/separator/separator';

const ResistanceSection = ({
  adjustments,
  toEquipType,
}: ResistanceSectionProps) => {

  const hasAny = hasAnyNonZeroAdjustment(adjustments, RESISTANCE_FIELDS);

  if (toEquipType !== InventoryItemTypes.RING) {
    return null;
  }

  if (!hasAny) {
    return null;
  }

  const Row = (label: string, raw: number | null | undefined) => {
    const value = Number(raw ?? 0);

    if (value === 0) {
      return null;
    }

    return (
      <Fragment key={label}>
        <dt className="font-medium text-gray-900 dark:text-gray-100">
          <div className="flex items-center">
            <StatInfoToolTip
              label={label.toLowerCase()}
              value={value}
              renderAsPercent
              align="left"
              size="sm"
            />
            <span className="min-w-0 break-words">{label}</span>
          </div>
        </dt>
        <dd>
          <AdjustmentChangeDisplay
            value={value}
            label={label}
            renderAsPercent
          />
        </dd>
      </Fragment>
    );
  };

  return (
    <Fragment>
      <h4 className="mt-3 mb-1 text-xs font-semibold uppercase tracking-wide text-mango-tango-500 dark:text-mango-tango-300">
        Resistance Adjustments
      </h4>
      <Separator />
      <dl className="grid grid-cols-[1fr_auto] items-center gap-x-4 gap-y-1">
        {Row('Spell Evasion', adjustments.spell_evasion_adjustment)}
        {Row('Healing Reduction', adjustments.healing_reduction_adjustment)}
        {Row(
          'Affix Damage Reduction',
          adjustments.affix_damage_reduction_adjustment
        )}
      </dl>
    </Fragment>
  );
};

export default ResistanceSection;
