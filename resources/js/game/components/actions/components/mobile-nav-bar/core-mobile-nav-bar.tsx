import clsx from 'clsx';
import React, { ReactNode } from 'react';

import { useIsMobile } from '../../partials/actions/hooks/use-is-mobile';
import { useManageCharacterCardVisibility } from '../../partials/floating-cards/character-details/hooks/use-manage-character-card-visibility';
import { useManageCraftingCardVisibility } from '../../partials/floating-cards/crafting-section/hooks/use-manage-crafting-card-visibility';
import { useManageMapSectionVisibility } from '../../partials/floating-cards/map-section/hooks/use-manage-map-section-visibility';
import { useManageShopVisibility } from '../../partials/floating-cards/map-section/hooks/use-manage-shop-visibility';

type ActiveKey = 'character' | 'craft' | 'map' | 'shop' | null;

const CoreMobileNavBar = (): ReactNode => {
  const { isMobile } = useIsMobile();

  const { openCharacterCard, showCharacterCard } =
    useManageCharacterCardVisibility();

  const { openCraftingCard, showCraftingCard } =
    useManageCraftingCardVisibility();

  const { openMapCard, showMapCard } = useManageMapSectionVisibility();

  const { openShop, showShopCard } = useManageShopVisibility();

  const getActiveKey = (): ActiveKey => {
    if (showCharacterCard) {
      return 'character';
    }

    if (showCraftingCard) {
      return 'craft';
    }

    if (showMapCard) {
      return 'map';
    }

    if (showShopCard) {
      return 'shop';
    }

    return null;
  };

  const renderItem = (
    _key: Exclude<ActiveKey, null> | 'quests',
    label: string,
    iconClass: string,
    onClick: () => void,
    isActive: boolean
  ): ReactNode => {
    return (
      <li className="flex items-stretch justify-center">
        <button
          type="button"
          onClick={onClick}
          aria-label={label}
          aria-current={isActive ? 'page' : undefined}
          className="w-full focus:outline-none"
        >
          <div className="h-full flex flex-col items-center justify-center">
            <i
              className={clsx(
                iconClass,
                'text-base',
                isActive && 'text-blue-600 dark:text-blue-400'
              )}
              aria-hidden="true"
            />
            <span
              className={clsx(
                'text-xs leading-3 mt-0.5',
                isActive && 'text-blue-600 dark:text-blue-400'
              )}
            >
              {label}
            </span>
          </div>
        </button>
      </li>
    );
  };

  const renderNav = (): ReactNode => {
    if (!isMobile) {
      return null;
    }

    const activeKey = getActiveKey();

    return (
      <>
        <div
          className="mobile-bottom-nav-spacer sm:hidden block h-16"
          aria-hidden="true"
        />
        <div className="mobile-bottom-nav sm:hidden fixed bottom-0 left-0 right-0 z-40 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 h-16 pb-[env(safe-area-inset-bottom)] shadow-[0_-4px_12px_rgba(0,0,0,0.08)]">
          <nav role="navigation" aria-label="Primary" className="h-full">
            <ul className="grid grid-cols-5 h-full text-gray-800 dark:text-gray-100">
              {renderItem(
                'character',
                'Character',
                'ra ra-player',
                openCharacterCard,
                activeKey === 'character'
              )}
              {renderItem(
                'craft',
                'Craft',
                'ra ra-anvil',
                openCraftingCard,
                activeKey === 'craft'
              )}
              {renderItem(
                'quests',
                'Quests',
                'far fa-comments',
                () => {},
                false
              )}
              {renderItem(
                'map',
                'Map',
                'ra ra-compass',
                openMapCard,
                activeKey === 'map'
              )}
              {renderItem(
                'shop',
                'Shop',
                'fas fa-store',
                openShop,
                activeKey === 'shop'
              )}
            </ul>
          </nav>
        </div>
      </>
    );
  };

  return renderNav();
};

export default CoreMobileNavBar;
