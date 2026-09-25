import ApiErrorAlert from 'api-handler/components/api-error-alert';
import clsx from 'clsx';
import React, { Fragment, useState } from 'react';

import { TOP_ADVANCED_CHILD_FIELDS } from './constants/item-comparison-constants';
import EquipItemActions from './equip-item-actions';
import ItemComparisonColumn from './partials/item-comparison/item-comparison-column';
import ItemComparisonProps from './types/item-comparison-props';
import { hasAnyNonZeroAdjustment } from './utils/item-comparison';
import { ItemComparisonRow } from '../../api-definitions/items/item-comparison-details';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import IconButton from 'ui/buttons/icon-button';
import Separator from 'ui/separator/separator';

const ItemComparison = ({
  comparisonDetails,
  show_buy_and_replace = false,
  is_purchasing,
  error_message,
  on_buy_and_replace,
}: ItemComparisonProps) => {
  const [showAdvanced, setShowAdvanced] = useState(false);
  const [showEquipActions, setShowEquipActions] = useState(false);

  const handleToggleAdvanced = () => {
    setShowAdvanced((previous) => !previous);
  };

  const handleShowEquipSection = () => {
    setShowEquipActions(true);
  };

  const handleCloseEquipSection = () => {
    setShowEquipActions(false);
  };

  const comparisonRows = (comparisonDetails.details ?? []).slice(0, 2);

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

  const advancedToggleLabel = showAdvanced
    ? 'Hide advanced details'
    : 'Show advanced details';

  const isSingle = comparisonRows.length === 1;
  const gridClasses = isSingle
    ? 'grid grid-cols-1'
    : 'grid grid-cols-1 md:grid-cols-2 gap-4';

  const renderRowSeparator = (index: number) => {
    if (index >= comparisonRows.length - 1) {
      return null;
    }

    return (
      <div className="my-6 block px-2 md:hidden">
        <Separator />
      </div>
    );
  };

  const renderBuyAndReplaceAction = () => {
    if (!show_buy_and_replace) {
      return null;
    }

    return (
      <IconButton
        additional_css="ml-4"
        on_click={handleShowEquipSection}
        variant={ButtonVariant.SUCCESS}
        label="Buy and replace"
        aria_label="Buy and replace"
      />
    );
  };

  const renderPurchaseError = () => {
    if (!error_message) {
      return null;
    }

    return <ApiErrorAlert apiError={error_message.message} />;
  };

  const renderEquipActions = () => {
    if (!showEquipActions) {
      return null;
    }

    return (
      <div className="my-4 space-y-2">
        {renderPurchaseError()}
        <EquipItemActions
          comparison_details={comparisonDetails}
          on_confirm_action={on_buy_and_replace}
          on_close_equip_action={handleCloseEquipSection}
          is_processing={is_purchasing}
        />
      </div>
    );
  };

  const renderComparisonRow = (row: ItemComparisonRow, index: number) => (
    <Fragment key={`${row.position}-${row.equipped_item.slot_id}`}>
      <div className="min-w-0">
        <ItemComparisonColumn
          row={row}
          showAdvanced={showAdvanced}
          showAdvancedChildUnderTop={showAdvancedChildUnderTop}
        />
      </div>

      {renderRowSeparator(index)}
    </Fragment>
  );

  return (
    <div className="space-y-3">
      <div className="flex items-center justify-end">
        <IconButton
          on_click={handleToggleAdvanced}
          icon={
            <i
              className={clsx('fas', showAdvanced ? 'fa-eye-slash' : 'fa-eye')}
              aria-hidden="true"
            />
          }
          variant={ButtonVariant.PRIMARY}
          label={advancedToggleLabel}
          additional_css="px-3"
          aria_label={advancedToggleLabel}
        />
        {renderBuyAndReplaceAction()}
      </div>

      {renderEquipActions()}

      <div className={gridClasses}>
        {comparisonRows.map(renderComparisonRow)}
      </div>
    </div>
  );
};

export default ItemComparison;
