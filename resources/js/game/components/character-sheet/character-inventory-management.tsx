import React, { ReactNode } from 'react';

import InventorySection from './partials/character-inventory/inventory-section';
import CharacterInventoryManagementProps from './types/character-inventory-management-props';

const CharacterInventoryManagement = ({
  character_id,
}: CharacterInventoryManagementProps): ReactNode => {
  return <InventorySection character_id={character_id} />;
};

export default CharacterInventoryManagement;
