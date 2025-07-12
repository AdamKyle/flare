import React, { useState } from 'react';

import ExpandedItemComparison from './partials/expanded-item-comparison';
import ItemComparisonProps from './types/item-comparison-props';
import {
  armourPositions,
  InventoryItemTypes,
} from '../../components/character-sheet/partials/character-inventory/enums/inventory-item-types';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';

const ItemComparison = ({ comparisonDetails }: ItemComparisonProps) => {
  const [expanded, setExpanded] = useState(false);

  const renderField = (label: string, value: number, isPercent = true) => {
    if (value === 0) return null;
    const display = isPercent ? `${(value * 100).toFixed(2)}%` : `${value}`;
    const color =
      value > 0
        ? 'text-emerald-500 dark:text-emerald-300'
        : 'text-rose-500 dark:text-rose-300';
    const prefix = value > 0 ? '+' : '';
    return (
      <React.Fragment key={label}>
        <Dt>{label}</Dt>
        <Dd>
          <span className={color}>{`${prefix}${display}`}</span>
        </Dd>
      </React.Fragment>
    );
  };

  const renderExpandedDetails = (
    detail: (typeof comparisonDetails.details)[0]
  ) => {
    if (!expanded) {
      return null;
    }

    return <ExpandedItemComparison expandedDetails={detail} />;
  };

  const columnsClass =
    comparisonDetails.details.length > 1 ? 'md:grid-cols-2' : '';

  return (
    <div>
      <div className={`grid grid-cols-1 ${columnsClass} gap-6`}>
        {comparisonDetails.details.map((detail) => {
          const isArmour = armourPositions.includes(
            detail.type as InventoryItemTypes
          );
          const statFields: Array<[string, number]> = [
            ['Strength', detail.str_adjustment],
            ['Dexterity', detail.dex_adjustment],
            ['Intelligence', detail.int_adjustment],
            ['Durability', detail.dur_adjustment],
            ['Charisma', detail.chr_adjustment],
            ['Agility', detail.agi_adjustment],
            ['Focus', detail.focus_adjustment],
          ];

          return (
            <div key={detail.name} className="space-y-6 p-4 md:p-6">
              <h3 className="text-lg font-semibold text-primary-700 dark:text-primary-300">
                {detail.name}
              </h3>
              <Separator />
              <Dl>
                <Dt>Equipped Position</Dt>
                <Dd>{detail.position}</Dd>
                {isArmour ? (
                  <>
                    <Dt>AC</Dt>
                    <Dd>{detail.ac_adjustment}</Dd>
                  </>
                ) : (
                  <>
                    <Dt>Damage</Dt>
                    <Dd>{detail.damage_adjustment}</Dd>
                  </>
                )}
              </Dl>
              <Separator />
              <div>
                <h4 className="text-sm font-medium text-danube-600 dark:text-danube-300 mb-2">
                  Stats
                </h4>
                <Separator />
                <Dl>
                  {statFields.map(([label, val]) =>
                    renderField(label, val, true)
                  )}
                </Dl>
              </div>
              {renderExpandedDetails(detail)}
            </div>
          );
        })}
      </div>
      <div className="text-center mt-4">
        <Button
          on_click={() => setExpanded(!expanded)}
          label={expanded ? 'Show Less Details' : 'See Expanded Details'}
          variant={ButtonVariant.PRIMARY}
        />
      </div>
    </div>
  );
};

export default ItemComparison;
