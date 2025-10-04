import React, { Fragment, useState } from 'react';

import { TOP_ADVANCED_CHILD_FIELDS } from './constants/item-comparison-constants';
import ItemComparisonColumn from './partials/item-comparison/item-comparison-column';
import ItemComparisonProps from './types/item-comparison-props';
import { hasAnyNonZeroAdjustment } from './utils/item-comparison';
import type { ItemComparisonRow } from '../../api-definitions/items/item-comparison-details';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import IconButton from 'ui/buttons/icon-button';
import Separator from 'ui/separator/separator';

const ItemComparison = ({
  comparisonDetails,
  item_name,
  show_buy_an_replace = false
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

  const isSingle = comparisonRows.length === 1;
  const gridClasses = isSingle
    ? 'grid grid-cols-1'
    : 'grid grid-cols-1 md:grid-cols-2 gap-4';

  const renderBuyAndReplaceAction = () => {
    if (!show_buy_an_replace) {
      return null;
    }

    return (
      <IconButton
        additional_css={'ml-4'}
        on_click={() => {}}
        variant={ButtonVariant.SUCCESS}
        label={'But and replace'}
        aria_label={'Purchase and Replace'}
      />
    )
  }

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
          aria_label={
            showAdvanced ? 'Hide advanced details' : 'Show advanced details'
          }
        />

      </div>

      <div className={gridClasses}>
        {comparisonRows.map((row, index) => (
          <Fragment key={index}>
            <div className="min-w-0">
              <ItemComparisonColumn
                row={row}
                heading={item_name}
                index={index}
                showAdvanced={showAdvanced}
                showAdvancedChildUnderTop={showAdvancedChildUnderTop}
              />
              {renderBuyAndReplaceAction()}
            </div>

            {index < comparisonRows.length - 1 && (
              <div className="block md:hidden my-6 px-2">
                <Separator />
              </div>
            )}
          </Fragment>
        ))}
      </div>
    </div>
  );
};

export default ItemComparison;
