import React from 'react';

import FactualLink from './factual-link';
import DefinitionRow from '../../viewable-sections/definition-row';
import InfoLabel from '../../viewable-sections/info-label';
import LocationRowProps from '../types/partials/location-row-props';

const LocationRow = ({
  heading,
  location,
  on_open_location: onOpenLocation,
  on_open_map: onOpenMap,
}: LocationRowProps) => {
  return (
    <>
      <DefinitionRow
        left={<InfoLabel label={heading} />}
        right={
          <FactualLink
            id={location.id}
            label={location.name}
            on_click={onOpenLocation}
          />
        }
      />
      <DefinitionRow
        left={<InfoLabel label="While On Map" />}
        right={
          <FactualLink
            id={location.game_map.id}
            label={location.game_map.name}
            on_click={onOpenMap}
          />
        }
      />
    </>
  );
};

export default LocationRow;
