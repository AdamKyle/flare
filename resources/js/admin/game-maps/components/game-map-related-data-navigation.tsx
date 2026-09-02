import React, { ReactNode } from 'react';

import GameMapRelatedDataNavigationProps from './types/game-map-related-data-navigation-props';
import { useGameMapRelatedDataEntries } from '../hooks/use-game-map-related-data-entries';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import IconButton from 'ui/buttons/icon-button';

/**
 * Game Map "Related Game Data" responsive navigation: opens a bounded
 * relationship browser SidePeek for each interconnected resource
 * (Locations, NPCs, Monsters, Quests, Quest Items). This is a feature-owned
 * composition (not the global `IconContainer`, whose narrow `lg:w-10` rail
 * is too small for labeled buttons and overlaps the Map image) with three
 * responsive treatments: a horizontal wrapping row above the content at
 * `sm`-`lg`, a full-width vertical rail beside the content at `lg+`, and a
 * fixed bottom navigation bar on mobile. Admin mutation actions (Edit Map,
 * Edit Locations) are rendered separately by the caller.
 */
const GameMapRelatedDataNavigation = ({
  game_map_id: gameMapId,
}: GameMapRelatedDataNavigationProps): ReactNode => {
  const entries = useGameMapRelatedDataEntries(gameMapId);

  const renderTabletNav = (): ReactNode => (
    <div className="hidden flex-wrap gap-2 sm:flex lg:hidden">
      {entries.map((entry) => (
        <IconButton
          key={entry.key}
          label={entry.label}
          icon={
            <i className={`${entry.icon_class} text-sm`} aria-hidden="true" />
          }
          variant={ButtonVariant.SERVER_MESSAGE_LINK}
          on_click={entry.on_click}
          additional_css="bg-glacier-700 hover:bg-glacier-600 focus:ring-glacier-400 dark:bg-glacier-800 dark:hover:bg-glacier-700"
        />
      ))}
    </div>
  );

  const renderDesktopNav = (): ReactNode => (
    <div className="hidden shrink-0 flex-col gap-2 lg:flex lg:w-40">
      {entries.map((entry) => (
        <IconButton
          key={entry.key}
          label={entry.label}
          icon={
            <i className={`${entry.icon_class} text-sm`} aria-hidden="true" />
          }
          variant={ButtonVariant.SERVER_MESSAGE_LINK}
          on_click={entry.on_click}
          additional_css="w-full bg-glacier-700 hover:bg-glacier-600 focus:ring-glacier-400 dark:bg-glacier-800 dark:hover:bg-glacier-700"
        />
      ))}
    </div>
  );

  const renderMobileNav = (): ReactNode => (
    <>
      <div
        className="mobile-bottom-nav-spacer block h-16 sm:hidden"
        aria-hidden="true"
      />
      <div className="mobile-bottom-nav fixed right-0 bottom-0 left-0 z-40 h-16 border-t border-gray-200 bg-white pb-[env(safe-area-inset-bottom)] shadow-[0_-4px_12px_rgba(0,0,0,0.08)] sm:hidden dark:border-gray-700 dark:bg-gray-900">
        <ul className="grid h-full grid-cols-5">
          {entries.map((entry) => (
            <li key={entry.key} className="flex items-stretch justify-center">
              <button
                type="button"
                onClick={entry.on_click}
                aria-label={
                  entry.key === 'quest-items' ? 'Quest Items' : entry.label
                }
                className="focus-visible:ring-glacier-400 hover:text-glacier-700 dark:hover:text-glacier-300 text-glacier-800 dark:text-glacier-100 w-full focus:outline-none focus-visible:ring-2 focus-visible:ring-inset"
              >
                <div className="flex h-full flex-col items-center justify-center">
                  <i
                    className={`${entry.icon_class} text-base`}
                    aria-hidden="true"
                  />
                  <span className="mt-0.5 text-xs leading-3">
                    {entry.mobile_label}
                  </span>
                </div>
              </button>
            </li>
          ))}
        </ul>
      </div>
    </>
  );

  return (
    <nav aria-label="Related Game Data" className="lg:shrink-0">
      {renderTabletNav()}
      {renderDesktopNav()}
      {renderMobileNav()}
    </nav>
  );
};

export default GameMapRelatedDataNavigation;
