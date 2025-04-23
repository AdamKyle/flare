import React from 'react';

import CharacterCard from '../floating-cards/character-details/character-card';
import { useManageCharacterCardVisibility } from '../floating-cards/character-details/hooks/use-manage-character-card-visibility';
import CraftingCard from '../floating-cards/crafting-section/crafting-card';
import { useManageCraftingCardVisibility } from '../floating-cards/crafting-section/hooks/use-manage-crafting-card-visibility';
import { useManageMapSectionVisibility } from '../floating-cards/map-section/hooks/use-manage-map-section-visibility';
import MapCard from '../floating-cards/map-section/map-card';

const ActionSection = () => {
  const { showCharacterCard } = useManageCharacterCardVisibility();

  const { showCraftingCard } = useManageCraftingCardVisibility();

  const { showMapCard } = useManageMapSectionVisibility();

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

  const renderShowMapSection = () => {
    if (!showMapCard) {
      return;
    }

    return <MapCard />;
  };

  return (
    <aside className="p-4 bg-gray-50 lg:border-l flex justify-center">
      {renderCharacterSheetSection()}
      {renderCraftingSection()}
      {renderShowMapSection()}
    </aside>
  );
};

export default ActionSection;
