import UseEquipmentManagementRestrictionDefinition from './deffinitions/use-equipment-management-restriction-definition';

import { useGameData } from 'game-data/hooks/use-game-data';

export const useEquipmentManagementRestriction =
  (): UseEquipmentManagementRestrictionDefinition => {
    const { gameData } = useGameData();

    const activeAutomation = gameData?.character?.active_automation ?? null;

    if (activeAutomation === null) {
      return {
        is_restricted: false,
        restriction_message: null,
      };
    }

    return {
      is_restricted: true,
      restriction_message: `You cannot change equipped items while ${activeAutomation.name} automation is running. Cancel it first.`,
    };
  };
