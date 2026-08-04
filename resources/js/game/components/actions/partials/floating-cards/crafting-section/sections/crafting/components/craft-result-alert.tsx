import clsx from 'clsx';
import React, { ReactNode } from 'react';

import CraftResultAlertProps from './types/craft-result-alert-props';
import { planeTextItemColors } from '../../../../../../../character-sheet/partials/character-inventory/styles/backpack-item-styles';
import { useOpenItemDetails } from '../../../../../../../chat-section/hooks/use-open-item-details';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { baseStyles } from 'ui/buttons/styles/link-buttons/base-styles';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import IndeterminateProgressBar from 'ui/progress/indeterminate-progress-bar';

const CraftResultAlert = ({
  characterId,
  isCrafting,
  error,
  successMessage,
  craftedInventorySlotId,
  craftedItemDetails,
}: CraftResultAlertProps): ReactNode => {
  const { openServerMessageItem } = useOpenItemDetails();

  const handleViewCraftedItem = () => {
    if (!craftedInventorySlotId) {
      return;
    }

    openServerMessageItem(characterId, craftedInventorySlotId);
  };

  const renderSuccessContent = (): ReactNode => {
    if (!craftedInventorySlotId || !craftedItemDetails) {
      return <span>{successMessage}</span>;
    }

    const itemColorClass = planeTextItemColors(craftedItemDetails);

    return (
      <span>
        {'You successfully crafted '}
        <button
          type="button"
          aria-label={`View ${craftedItemDetails.name} details`}
          className={clsx(baseStyles(), itemColorClass)}
          onClick={handleViewCraftedItem}
        >
          {craftedItemDetails.name}
        </button>
        {'.'}
      </span>
    );
  };

  if (isCrafting) {
    return (
      <IndeterminateProgressBar
        label="Crafting..."
        variant={ProgressBarVariant.PRIMARY}
      />
    );
  }

  if (!error && !successMessage) {
    return null;
  }

  return (
    <div>
      {error && <Alert variant={AlertVariant.DANGER}>{error}</Alert>}
      {successMessage && (
        <Alert variant={AlertVariant.SUCCESS}>{renderSuccessContent()}</Alert>
      )}
    </div>
  );
};

export default CraftResultAlert;
