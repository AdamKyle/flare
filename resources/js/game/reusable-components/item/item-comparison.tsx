import React, { useState } from 'react';

import { TOP_ADVANCED_CHILD_FIELDS } from './constants/item-comparison-constants';
import ItemComparisonColumn from './partials/item-comparison/item-comparison-column';
import ItemComparisonProps from './types/item-comparison-props';
import { hasAnyNonZeroAdjustment } from './utils/item-comparison';
import type { ItemComparisonRow } from '../../api-definitions/items/item-comparison-details';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import IconButton from 'ui/buttons/icon-button';

const ItemComparison = ({
  comparisonDetails,
  item_name,
}: ItemComparisonProps) => {
  const [showAdvanced, setShowAdvanced] = useState(false);

  const handleToggleAdvanced = () => {
    setShowAdvanced((previous) => !previous);
  };

  const comparisonRows = (
    (comparisonDetails ?? []) as ItemComparisonRow[]
  ).slice(0, 2);

  if (comparisonRows.length === 0) {
    return null;
  }

  const showAdvancedChildUnderTop =
    showAdvanced &&
    comparisonRows.some((row) =>
      hasAnyNonZeroAdjustment(
        row.comparison.adjustments,
        TOP_ADVANCED_CHILD_FIELDS
      )
    );

  return (
    <div className="space-y-3">
      <div className="flex items-center justify-end">
        <IconButton
          on_click={handleToggleAdvanced}
          icon={
            <i
              className={`fas ${showAdvanced ? 'fa-eye-slash' : 'fa-eye'}`}
              aria-hidden="true"
            />
          }
          variant={ButtonVariant.PRIMARY}
          label={
            showAdvanced ? 'Hide advanced details' : 'Show advanced details'
          }
          additional_css="px-3"
          aria_lebel={
            showAdvanced ? 'Hide advanced details' : 'Show advanced details'
          }
        />
      </div>

      <div
        className={`grid gap-4 ${comparisonRows.length === 2 ? 'md:grid-cols-2' : 'grid-cols-1'}`}
      >
        {comparisonRows.map((row, index) => (
          <ItemComparisonColumn
            key={index}
            row={row}
            heading={item_name}
            index={index}
            showAdvanced={showAdvanced}
            showAdvancedChildUnderTop={showAdvancedChildUnderTop}
          />
        ))}
      </div>
    </div>
  );
};

export default ItemComparison;
