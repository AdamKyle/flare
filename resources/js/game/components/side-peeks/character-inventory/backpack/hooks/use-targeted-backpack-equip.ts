import { useCallback } from 'react';

import UseTargetedBackpackEquipDefinition from './definitions/use-targeted-backpack-equip-definition';
import UseTargetedBackpackEquipParams from './definitions/use-targeted-backpack-equip-params';
import { useEquipmentManagementRestriction } from '../../hooks/use-equipment-management-restriction';
import { useEquipItem } from '../../inventory-item/api/hooks/use-equip-item';

export const useTargetedBackpackEquip = ({
  character_id: characterId,
  target_position: targetPosition,
  on_equipment_changed: onEquipmentChanged,
  refresh_backpack: refreshBackpack,
  close_item_detail: closeItemDetail,
  set_success_message: setSuccessMessage,
}: UseTargetedBackpackEquipParams): UseTargetedBackpackEquipDefinition => {
  const {
    is_restricted: isRestricted,
    restriction_message: restrictionMessage,
  } = useEquipmentManagementRestriction();

  const onEquipSuccess = useCallback(
    (message: string): void => {
      setSuccessMessage(message);
      closeItemDetail();
      refreshBackpack();
      onEquipmentChanged?.();
    },
    [closeItemDetail, onEquipmentChanged, refreshBackpack, setSuccessMessage]
  );

  const { loading, error, resetError, setRequestParams } = useEquipItem({
    character_id: characterId,
    on_success: onEquipSuccess,
  });

  const resetFeedback = useCallback((): void => {
    resetError();
    setSuccessMessage(null);
  }, [resetError, setSuccessMessage]);

  return {
    loading,
    error,
    restriction_message: restrictionMessage,
    is_restricted: isRestricted,
    resetFeedback,
    equipSelectedItem: (selectedItem) => {
      if (targetPosition === undefined) {
        return;
      }

      setRequestParams({
        slot_id: selectedItem.slot_id,
        equip_type: selectedItem.type,
        position: targetPosition,
      });
    },
  };
};
