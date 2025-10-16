import React, { ReactNode } from 'react';

import CharacterInventoryMobileNavBar from './character-inventory-mobile-nav-bar';
import CoreMobileNavBar from './core-mobile-nav-bar';
import { useCharacterInventoryVisibility } from '../../../hooks/use-character-inventory-visibility';
import { useIsMobile } from '../../partials/actions/hooks/use-is-mobile';

const MobileNav = (): ReactNode => {
  const { isMobile } = useIsMobile();

  const { showCharacterInventory } = useCharacterInventoryVisibility();

  const renderNav = (): ReactNode => {
    if (!isMobile) {
      return null;
    }

    if (showCharacterInventory) {
      return <CharacterInventoryMobileNavBar />;
    }

    return <CoreMobileNavBar />;
  };

  return renderNav();
};

export default MobileNav;
