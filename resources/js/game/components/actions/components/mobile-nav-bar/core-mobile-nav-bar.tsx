import clsx from 'clsx';
import { AnimatePresence, motion, useReducedMotion } from 'framer-motion';
import React, { ReactNode, useState } from 'react';

import MobileActivityPanel from '../../../activity/components/mobile-activity-panel';
import { useActivityActions } from '../../../activity/hooks/use-activity-actions';
import { useManageQuestLogVisibility } from '../../../quests/hooks/use-manage-quest-log-visibility';
import { useQuestLogVisibility } from '../../../quests/hooks/use-quest-log-visibility';
import { useIsMobile } from '../../partials/actions/hooks/use-is-mobile';
import { useManageCharacterCardVisibility } from '../../partials/floating-cards/character-details/hooks/use-manage-character-card-visibility';
import { useManageCraftingCardVisibility } from '../../partials/floating-cards/crafting-section/hooks/use-manage-crafting-card-visibility';
import { useManageMapSectionVisibility } from '../../partials/floating-cards/map-section/hooks/use-manage-map-section-visibility';
import { useManageShopVisibility } from '../../partials/floating-cards/map-section/hooks/use-manage-shop-visibility';
import CharacterActiveBoonIndicator from '../../partials/icon-section/character-active-boon-indicator';
import { useCharacterActiveBoonStatus } from '../../partials/icon-section/hooks/use-character-active-boon-status';

type ActiveKey =
  'character' | 'craft' | 'map' | 'shop' | 'quests' | 'activity' | null;

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

  const { has_new_announcements } = useActivityActions();
  const reduceMotion = useReducedMotion();
  const [isActivityOpen, setIsActivityOpen] = useState(false);

  const activityAriaLabel = has_new_announcements
    ? 'Activity, new announcements available'
    : 'Activity';

  const handleOpenActivity = (): void => {
    setIsActivityOpen(true);
  };

  const handleCloseActivity = (): void => {
    setIsActivityOpen(false);
  };

  const getActiveKey = (): ActiveKey => {
    if (isActivityOpen) {
      return 'activity';
    }

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
    _key: Exclude<ActiveKey, null>,
    label: string,
    iconClass: string,
    onClick: () => void,
    isActive: boolean,
    statusIndicator?: ReactNode,
    ariaLabel?: string,
    isPulsing = false
  ): ReactNode => {
    const itemContent = (
      <>
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
            <span className="absolute -top-1 -right-1">{statusIndicator}</span>
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
      </>
    );

    return (
      <li className="flex items-stretch justify-center">
        <button
          type="button"
          onClick={onClick}
          aria-label={ariaLabel ?? label}
          aria-current={isActive ? 'page' : undefined}
          className="w-full focus:outline-none"
        >
          {renderItemContent(itemContent, isPulsing)}
        </button>
      </li>
    );
  };

  const renderItemContent = (
    itemContent: ReactNode,
    isPulsing: boolean
  ): ReactNode => {
    const contentClassName = 'flex h-full flex-col items-center justify-center';

    if (!isPulsing) {
      return <div className={contentClassName}>{itemContent}</div>;
    }

    if (reduceMotion) {
      return (
        <div
          className={clsx(
            contentClassName,
            'text-mango-tango-600 dark:text-mango-tango-400'
          )}
        >
          {itemContent}
        </div>
      );
    }

    return (
      <motion.div
        className={clsx(
          contentClassName,
          '[--activity-pulse-accent:var(--color-mango-tango-600)] [--activity-pulse-base:var(--color-gray-800)] dark:[--activity-pulse-accent:var(--color-mango-tango-400)] dark:[--activity-pulse-base:var(--color-gray-100)]'
        )}
        animate={{
          color: ['var(--activity-pulse-base)', 'var(--activity-pulse-accent)'],
        }}
        transition={{
          duration: 1.2,
          ease: 'easeInOut',
          repeat: Infinity,
          repeatType: 'reverse',
        }}
      >
        {itemContent}
      </motion.div>
    );
  };

  const renderActivityPanel = (): ReactNode => {
    if (!isActivityOpen) {
      return null;
    }

    return (
      <MobileActivityPanel
        key="mobile-activity-panel"
        on_close={handleCloseActivity}
      />
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
        <AnimatePresence>{renderActivityPanel()}</AnimatePresence>
        <div className="mobile-bottom-nav fixed right-0 bottom-0 left-0 z-40 h-16 border-t border-gray-200 bg-white pb-[env(safe-area-inset-bottom)] shadow-[0_-4px_12px_rgba(0,0,0,0.08)] sm:hidden dark:border-gray-700 dark:bg-gray-900">
          <nav role="navigation" aria-label="Primary" className="h-full">
            <ul className="grid h-full grid-cols-6 text-gray-800 dark:text-gray-100">
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
              {renderItem(
                'activity',
                'Activity',
                'far fa-bell',
                handleOpenActivity,
                activeKey === 'activity',
                undefined,
                activityAriaLabel,
                has_new_announcements
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
