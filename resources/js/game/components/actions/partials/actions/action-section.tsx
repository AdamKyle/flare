import React from 'react';

import CharacterCard from '../floating-cards/character-details/character-card';
import { useManageCharacterCardVisibility } from '../floating-cards/character-details/hooks/use-manage-character-card-visibility';
import CraftingCard from '../floating-cards/crafting-section/crafting-card';
import { useManageCraftingCardVisibility } from '../floating-cards/crafting-section/hooks/use-manage-crafting-card-visibility';

const ActionSection = () => {
  const { showCharacterCard } = useManageCharacterCardVisibility();

  const { showCraftingCard } = useManageCraftingCardVisibility();

  const renderCharacterSheetSection = () => {
    if (!showCharacterCard) {
      return;
    }

    return <CharacterCard />;
  };

  const renderCraftingSection = () => {
    if (!showCraftingCard) {
      return;
    }

    return <CraftingCard />;
  };

  return (
    <aside className="p-4 bg-gray-50 lg:border-l flex justify-center">
      {renderCharacterSheetSection()}
      {renderCraftingSection()}
    </aside>
  );
};

export default ActionSection;
