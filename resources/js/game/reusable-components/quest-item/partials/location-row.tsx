import React, { ReactNode } from 'react';

import FactualLink from './factual-link';
import { relationshipRowClassName } from './relationship-row-styles';
import LocationRowProps from '../types/partials/location-row-props';

/**
 * Compact clickable Location relationship row: the Location name is the
 * primary row title, with the relationship label and Game Map shown as an
 * independently clickable identity on a secondary line.
 */
const LocationRow = ({
  heading,
  location,
  on_open_location: onOpenLocation,
  on_open_map: onOpenMap,
}: LocationRowProps): ReactNode => (
  <div className={relationshipRowClassName}>
    <p className="text-glacier-900 dark:text-glacier-100 font-medium">
      <FactualLink
        id={location.id}
        label={location.name}
        on_click={onOpenLocation}
      />
    </p>
    <p className="text-glacier-600 dark:text-glacier-400 text-xs">
      {heading}
      {' · '}
      <FactualLink
        id={location.game_map.id}
        label={location.game_map.name}
        on_click={onOpenMap}
      />
    </p>
  </div>
);

export default LocationRow;
