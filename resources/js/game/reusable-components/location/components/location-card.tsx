import React, { ReactNode } from 'react';

import {
  locationCardBaseStyles,
  locationCardFocusRingStyles,
  locationCardIconStyles,
  locationCardPrimaryTextStyles,
  locationCardSecondaryTextStyles,
  locationCardThemeStyles,
} from '../styles/location-card-styles';
import LocationCardProps from '../types/location-card-props';

/**
 * Canonical, permission-neutral Emerald Location relationship card: showing
 * Location name, optional Type label, coordinates when both X and Y are
 * available, and an optional Game Map name. Renders as a single full-width
 * interactive `button` when `on_open_location` is supplied, or as a
 * semantically meaningful noninteractive `<article>` when no navigation
 * callback is supplied, for permission-neutral factual contexts with no
 * navigation available — matching the canonical `QuestCard` pattern.
 */
const LocationCard = ({
  location_id: locationId,
  name,
  type_label: typeLabel,
  x,
  y,
  game_map_name: gameMapName,
  on_open_location: onOpenLocation,
}: LocationCardProps): ReactNode => {
  const hasCoordinates = typeof x === 'number' && typeof y === 'number';

  const renderMeta = (): ReactNode => {
    const hasMeta =
      Boolean(typeLabel) || hasCoordinates || Boolean(gameMapName);

    if (!hasMeta) {
      return null;
    }

    return (
      <div
        className={`flex flex-col gap-0.5 text-xs ${locationCardSecondaryTextStyles()}`}
      >
        {typeLabel && <span>{typeLabel}</span>}
        {hasCoordinates && (
          <span>
            X {x}, Y {y}
          </span>
        )}
        {gameMapName && <span>Map: {gameMapName}</span>}
      </div>
    );
  };

  const renderCardContent = (): ReactNode => (
    <>
      <i
        className={`fas fa-map-marker-alt text-2xl ${locationCardIconStyles()}`}
        aria-hidden="true"
      />
      <div className="flex min-w-0 flex-1 flex-col gap-1">
        <span
          className={`text-sm font-semibold break-words ${locationCardPrimaryTextStyles()}`}
        >
          {name}
        </span>
        {renderMeta()}
      </div>
    </>
  );

  if (onOpenLocation) {
    return (
      <button
        type="button"
        onClick={() => onOpenLocation(locationId)}
        aria-label={`Open Location details for ${name}`}
        className={`${locationCardBaseStyles()} ${locationCardThemeStyles()} ${locationCardFocusRingStyles()}`}
      >
        {renderCardContent()}
      </button>
    );
  }

  return (
    <article
      aria-label={name}
      className={`${locationCardBaseStyles()} ${locationCardThemeStyles()}`}
    >
      {renderCardContent()}
    </article>
  );
};

export default LocationCard;
