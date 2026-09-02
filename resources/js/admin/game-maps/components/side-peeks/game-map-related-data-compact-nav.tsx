import React, { ReactNode } from 'react';

import { useGameMapRelatedDataEntries } from '../../hooks/use-game-map-related-data-entries';
import GameMapRelatedDataNavigationProps from '../types/game-map-related-data-navigation-props';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import IconButton from 'ui/buttons/icon-button';

/**
 * Compact Game Map "Related Game Data" navigation for use inside the Game
 * Map factual SidePeek, which does not have the page-level side/bottom
 * navigation layout available to it. Wraps the same Glacier `IconButton`
 * treatment as the standalone responsive navigation, reusing the same
 * relation-open handlers, without a fixed bottom bar.
 */
const GameMapRelatedDataCompactNav = ({
  game_map_id: gameMapId,
  on_open_related: onOpenRelated,
}: GameMapRelatedDataNavigationProps): ReactNode => {
  const entries = useGameMapRelatedDataEntries(gameMapId, onOpenRelated);

  return (
    <nav aria-label="Related Game Data">
      <div className="mx-2 flex flex-col gap-2">
        {entries.map((entry) => (
          <IconButton
            key={entry.key}
            label={entry.label}
            icon={
              <i className={`${entry.icon_class} text-sm`} aria-hidden="true" />
            }
            variant={ButtonVariant.SERVER_MESSAGE_LINK}
            on_click={entry.on_click}
            additional_css="w-full bg-glacier-700 hover:bg-glacier-600 focus:ring-glacier-400 dark:bg-glacier-800 dark:hover:bg-glacier-700 text-sm px-3 py-1.5"
          />
        ))}
      </div>
    </nav>
  );
};

export default GameMapRelatedDataCompactNav;
