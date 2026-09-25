import { AnimatePresence } from 'framer-motion';
import React, { ReactNode, useMemo, useState } from 'react';

import ReplacementPicker from './replacement-picker';
import EquippedItemDetailsProps from './types/equipped-item-details-props';
import { useEquipmentManagementRestriction } from '../hooks/use-equipment-management-restriction';
import InventoryItem from '../inventory-item/inventory-item';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { useSidePeekOptions } from 'ui/side-peek/options/hooks/use-side-peek-options';
import SidePeekOptionDefinition from 'ui/side-peek/options/types/side-peek-option-definition';

const EquippedItemDetails = ({
  character_id,
  slot_id,
  position,
  item_name,
  on_equipment_changed,
}: EquippedItemDetailsProps): ReactNode => {
  const { is_restricted, restriction_message } =
    useEquipmentManagementRestriction();

  const [isChoosingReplacement, setIsChoosingReplacement] = useState(false);
  const [replacementMessage, setReplacementMessage] = useState<string | null>(
    null
  );

  const hasReplacedItem = replacementMessage !== null;

  const footerOptions = useMemo((): SidePeekOptionDefinition[] => {
    if (isChoosingReplacement || hasReplacedItem) {
      return [];
    }

    return [
      {
        id: 'replace-equipped-item',
        label: 'Replace',
        aria_label: `Replace ${item_name}`,
        variant: ButtonVariant.PRIMARY,
        disabled: is_restricted,
        on_click: () => setIsChoosingReplacement(true),
      },
    ];
  }, [isChoosingReplacement, hasReplacedItem, item_name, is_restricted]);

  useSidePeekOptions(footerOptions);

  const handleCloseReplacement = (): void => {
    setIsChoosingReplacement(false);
  };

  const handleReplacementEquipped = (successMessage: string): void => {
    setIsChoosingReplacement(false);
    setReplacementMessage(successMessage);
    on_equipment_changed();
  };

  const renderRestrictionNotice = (): ReactNode => {
    if (restriction_message === null || hasReplacedItem) {
      return null;
    }

    return (
      <div className="px-4 pb-4">
        <Alert variant={AlertVariant.WARNING}>{restriction_message}</Alert>
      </div>
    );
  };

  const renderDetails = (): ReactNode => {
    if (hasReplacedItem) {
      return (
        <div className="px-4">
          <Alert variant={AlertVariant.SUCCESS}>{replacementMessage}</Alert>
          <p className="mt-4 text-sm text-gray-700 dark:text-gray-300">
            Select the slot again from your equipped gear to view the new item.
          </p>
        </div>
      );
    }

    return (
      <InventoryItem
        slot_id={slot_id}
        character_id={character_id}
        on_action={on_equipment_changed}
        show_actions={false}
      />
    );
  };

  const renderReplacementPicker = (): ReactNode => {
    if (!isChoosingReplacement) {
      return null;
    }

    return (
      <ReplacementPicker
        character_id={character_id}
        target_position={position}
        target_item_name={item_name}
        is_equipment_restricted={is_restricted}
        on_close={handleCloseReplacement}
        on_equipped={handleReplacementEquipped}
      />
    );
  };

  return (
    <div className="relative flex h-full min-h-0 flex-col overflow-hidden">
      <div className="min-h-0 flex-1 overflow-y-auto py-4">
        {renderRestrictionNotice()}
        {renderDetails()}
      </div>
      <AnimatePresence mode="wait">{renderReplacementPicker()}</AnimatePresence>
    </div>
  );
};

export default EquippedItemDetails;
