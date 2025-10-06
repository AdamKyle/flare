import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { Fragment, useState } from 'react';

import { TOP_ADVANCED_CHILD_FIELDS } from './constants/item-comparison-constants';
import { ItemPositions } from './enums/item-positions';
import EquipItemActions from './equip-item-actions';
import ItemComparisonColumn from './partials/item-comparison/item-comparison-column';
import ItemComparisonProps from './types/item-comparison-props';
import { hasAnyNonZeroAdjustment } from './utils/item-comparison';
import type { ItemComparisonRow } from '../../api-definitions/items/item-comparison-details';
import { InventoryItemTypes } from '../../components/character-sheet/partials/character-inventory/enums/inventory-item-types';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import IconButton from 'ui/buttons/icon-button';
import Separator from 'ui/separator/separator';

const ItemComparison = ({
  comparisonDetails,
  item_name,
  show_buy_and_replace = false,
  is_purchasing,
  error_message,
  set_request_params,
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

  const handleBuyAndReplace = (
    position: ItemPositions,
    slot_id: number,
    type: InventoryItemTypes,
    item_id_to_buy: number
  ) => {
    set_request_params({
      position: position,
      slot_id: slot_id,
      equip_type: type,
      item_id_to_buy: item_id_to_buy,
    });
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
    if (!show_buy_and_replace) {
      return null;
    }

    return (
      <IconButton
        additional_css={'ml-4'}
        on_click={handleShowEquipSection}
        variant={ButtonVariant.SUCCESS}
        label={'But and replace'}
        aria_label={'Purchase and Replace'}
      />
    );
  };

  const renderEquipActions = () => {
    if (!showEquipActions) {
      return null;
    }

    if (error_message) {
      return <ApiErrorAlert apiError={error_message.message} />;
    }

    return (
      <div className="my-4">
        <EquipItemActions
          comparisonDetails={comparisonDetails}
          on_buy_and_replace={handleBuyAndReplace}
          on_close_buy_and_equip={handleCloseEquipSection}
          is_purchasing={is_purchasing}
        />
      </div>
    );
  };

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
        {renderBuyAndReplaceAction()}
      </div>

      {renderEquipActions()}

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
