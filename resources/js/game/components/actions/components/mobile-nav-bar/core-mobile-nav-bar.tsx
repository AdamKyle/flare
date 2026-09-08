import clsx from 'clsx';
import React, { ReactNode } from 'react';

import { useManageQuestLogVisibility } from '../../../quests/hooks/use-manage-quest-log-visibility';
import { useQuestLogVisibility } from '../../../quests/hooks/use-quest-log-visibility';
import { useIsMobile } from '../../partials/actions/hooks/use-is-mobile';
import { useManageCharacterCardVisibility } from '../../partials/floating-cards/character-details/hooks/use-manage-character-card-visibility';
import { useManageCraftingCardVisibility } from '../../partials/floating-cards/crafting-section/hooks/use-manage-crafting-card-visibility';
import { useManageMapSectionVisibility } from '../../partials/floating-cards/map-section/hooks/use-manage-map-section-visibility';
import { useManageShopVisibility } from '../../partials/floating-cards/map-section/hooks/use-manage-shop-visibility';
import CharacterActiveBoonIndicator from '../../partials/icon-section/character-active-boon-indicator';
import { useCharacterActiveBoonStatus } from '../../partials/icon-section/hooks/use-character-active-boon-status';

type ActiveKey = 'character' | 'craft' | 'map' | 'shop' | 'quests' | null;

const CoreMobileNavBar = (): ReactNode => {
  const { isMobile } = useIsMobile();

  const { has_active_boons: hasActiveBoons } = useCharacterActiveBoonStatus();

  const { openCharacterCard, showCharacterCard } =
    useManageCharacterCardVisibility();

  const { openCraftingCard, showCraftingCard } =
    useManageCraftingCardVisibility();

  const { openMapCard, showMapCard } = useManageMapSectionVisibility();

  const { openShop, showShopCard } = useManageShopVisibility();

  const { showQuestLog } = useQuestLogVisibility();
  const { openQuestLog } = useManageQuestLogVisibility();

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

    if (showQuestLog) {
      return 'quests';
    }

    return null;
  };

  const renderItem = (
    _key: Exclude<ActiveKey, null> | 'quests',
    label: string,
    iconClass: string,
    onClick: () => void,
    isActive: boolean,
    statusIndicator?: ReactNode
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
          <div className="flex h-full flex-col items-center justify-center">
            <span className="relative inline-flex">
              <i
                className={clsx(
                  iconClass,
                  'text-base',
                  isActive && 'text-blue-600 dark:text-blue-400'
                )}
                aria-hidden="true"
              />
              {statusIndicator && (
                <span className="absolute -top-1 -right-1">
                  {statusIndicator}
                </span>
              )}
            </span>
            <span
              className={clsx(
                'mt-0.5 text-xs leading-3',
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
          className="mobile-bottom-nav-spacer block h-16 sm:hidden"
          aria-hidden="true"
        />
        <div className="mobile-bottom-nav fixed right-0 bottom-0 left-0 z-40 h-16 border-t border-gray-200 bg-white pb-[env(safe-area-inset-bottom)] shadow-[0_-4px_12px_rgba(0,0,0,0.08)] sm:hidden dark:border-gray-700 dark:bg-gray-900">
          <nav role="navigation" aria-label="Primary" className="h-full">
            <ul className="grid h-full grid-cols-5 text-gray-800 dark:text-gray-100">
              {renderItem(
                'character',
                'Character',
                'ra ra-player',
                openCharacterCard,
                activeKey === 'character',
                <CharacterActiveBoonIndicator active={hasActiveBoons} />
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
                openQuestLog,
                activeKey === 'quests'
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
