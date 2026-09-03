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
 * Canonical, permission-neutral Emerald Location relationship card: a
 * single full-width interactive button showing Location name, optional
 * Type label, and coordinates when both X and Y are available.
 */
const LocationCard = ({
  location_id: locationId,
  name,
  type_label: typeLabel,
  x,
  y,
  on_open_location: onOpenLocation,
}: LocationCardProps): ReactNode => {
  const hasCoordinates = typeof x === 'number' && typeof y === 'number';

  const renderMeta = (): ReactNode => {
    if (!typeLabel && !hasCoordinates) {
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
      </div>
    );
  };

  return (
    <button
      type="button"
      onClick={() => onOpenLocation(locationId)}
      aria-label={`Open Location details for ${name}`}
      className={`${locationCardBaseStyles()} ${locationCardThemeStyles()} ${locationCardFocusRingStyles()}`}
    >
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
    </button>
  );
};

export default LocationCard;
