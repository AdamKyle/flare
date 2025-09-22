import React from 'react';

import DefinitionRow from '../../viewable-sections/definition-row';
import InfoLabel from '../../viewable-sections/info-label';
import LocationRowProps from '../types/partials/location-row-props';

const LocationRow = ({ heading, name, map }: LocationRowProps) => {
  return (
    <>
      <DefinitionRow
        left={<InfoLabel label={heading} />}
        right={<span className="text-gray-800 dark:text-gray-200">{name}</span>}
      />
      {map ? (
        <DefinitionRow
          left={<InfoLabel label="While On Map" />}
          right={
            <span className="text-gray-800 dark:text-gray-200">{map}</span>
          }
        />
      ) : null}
    </>
  );
};

export default LocationRow;
