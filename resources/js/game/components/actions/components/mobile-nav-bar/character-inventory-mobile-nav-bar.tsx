import clsx from 'clsx';
import React, { ReactNode } from 'react';

import { useOpenCharacterBackpack } from '../../../character-sheet/partials/character-inventory/hooks/use-open-character-backpack';
import { useOpenCharacterGemBag } from '../../../character-sheet/partials/character-inventory/hooks/use-open-character-gem-bag';
import { useOpenCharacterSets } from '../../../character-sheet/partials/character-inventory/hooks/use-open-character-sets';
import { useOpenCharacterUsableInventory } from '../../../character-sheet/partials/character-inventory/hooks/use-open-character-usable-inventory';
import { useIsMobile } from '../../partials/actions/hooks/use-is-mobile';

import { useGameData } from 'game-data/hooks/use-game-data';

const CharacterInventoryMobileNavBar = (): ReactNode => {
  const { isMobile } = useIsMobile();

  const { gameData } = useGameData();
  const characterId = gameData?.character?.id ?? null;

  const { openBackpack } = useOpenCharacterBackpack();
  const { openUsableInventory } = useOpenCharacterUsableInventory({
    character_id: characterId || 0,
  });
  const { openGemBag } = useOpenCharacterGemBag({
    character_id: characterId || 0,
  });
  const { openSets } = useOpenCharacterSets({ character_id: characterId || 0 });

  const handleBackpack = () => {
    if (!characterId) {
      return;
    }

    openBackpack();
  };

  const handleUsable = () => {
    if (!characterId) {
      return;
    }

    openUsableInventory();
  };

  const handleGems = () => {
    if (!characterId) {
      return;
    }

    openGemBag();
  };

  const handleSets = () => {
    if (!characterId) {
      return;
    }

    openSets();
  };

  const renderItem = (
    label: string,
    iconClass: string,
    onClick: () => void
  ): ReactNode => {
    return (
      <li className="flex items-stretch justify-center">
        <button
          type="button"
          onClick={onClick}
          aria-label={label}
          className="w-full focus:outline-none"
        >
          <div className="h-full flex flex-col items-center justify-center">
            <i className={clsx(iconClass, 'text-base')} aria-hidden="true" />
            <span className="text-xs leading-3 mt-0.5">{label}</span>
          </div>
        </button>
      </li>
    );
  };

  const renderNav = (): ReactNode => {
    if (!isMobile) {
      return null;
    }

    return (
      <>
        <div
          className="mobile-bottom-nav-spacer sm:hidden block h-16"
          aria-hidden="true"
        />
        <div className="mobile-bottom-nav sm:hidden fixed bottom-0 left-0 right-0 z-40 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 h-16 pb-[env(safe-area-inset-bottom)] shadow-[0_-4px_12px_rgba(0,0,0,0.08)]">
          <nav
            role="navigation"
            aria-label="Inventory actions"
            className="h-full"
          >
            <ul className="grid grid-cols-4 h-full text-gray-800 dark:text-gray-100">
              {renderItem('Backpack', 'ra ra-player', handleBackpack)}
              {renderItem('Usable', 'ra ra-potion', handleUsable)}
              {renderItem('Gems', 'ra ra-gem', handleGems)}
              {renderItem('Sets', 'ra ra-helmet', handleSets)}
            </ul>
          </nav>
        </div>
      </>
    );
  };

  return renderNav();
};

export default CharacterInventoryMobileNavBar;
