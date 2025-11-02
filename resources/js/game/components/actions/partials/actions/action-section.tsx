import React from 'react';

import CharacterCard from '../floating-cards/character-details/character-card';
import { useManageCharacterCardVisibility } from '../floating-cards/character-details/hooks/use-manage-character-card-visibility';
import CraftingCard from '../floating-cards/crafting-section/crafting-card';
import { useManageCraftingCardVisibility } from '../floating-cards/crafting-section/hooks/use-manage-crafting-card-visibility';
import { useManageMapSectionVisibility } from '../floating-cards/map-section/hooks/use-manage-map-section-visibility';
import { useManageShopVisibility } from '../floating-cards/map-section/hooks/use-manage-shop-visibility';
import MapCard from '../floating-cards/map-section/map-card';
import ShopCard from '../floating-cards/shop-section/shop-card';

const ActionSection = () => {
  const { showCharacterCard } = useManageCharacterCardVisibility();

  const { showCraftingCard } = useManageCraftingCardVisibility();

  const { showMapCard } = useManageMapSectionVisibility();

  const { showShopCard } = useManageShopVisibility();

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

  const renderShopSection = () => {
    if (!showShopCard) {
      return;
    }

    return <ShopCard />;
  };

  return (
    <aside className="flex w-full justify-center p-4 md:w-auto">
      {renderCharacterSheetSection()}
      {renderCraftingSection()}
      {renderShowMapSection()}
      {renderShopSection()}
    </aside>
  );
};

export default ActionSection;
