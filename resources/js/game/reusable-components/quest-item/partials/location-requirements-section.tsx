import React from 'react';

import RelationshipGroup from './relationship-group';
import LocationCard from '../../location/components/location-card';
import LocationRequirementsSectionProps from '../types/partials/location-requirements-section-props';

const LocationsRequireSection = ({
  item,
  showSeparator,
  navigation,
}: LocationRequirementsSectionProps) => {
  const requiredLocations = item.required_locations || [];

  if (requiredLocations.length === 0) {
    return null;
  }

  return (
    <RelationshipGroup
      title="Locations That Require This Item"
      show_separator={showSeparator}
    >
      {requiredLocations.map((requiredLocation) => (
        <LocationCard
          key={`required-location-${requiredLocation.id}`}
          location_id={requiredLocation.id}
          name={requiredLocation.name}
          game_map_name={requiredLocation.game_map.name}
          on_open_location={navigation.on_open_location}
        />
      ))}
    </RelationshipGroup>
  );
};

export default LocationsRequireSection;
