import React from 'react';

import LocationRow from './location-row';
import RelationshipGroup from './relationship-group';
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
        <LocationRow
          key={`required-location-${requiredLocation.id}`}
          heading="Required at location"
          location={requiredLocation}
          on_open_location={navigation.on_open_location}
          on_open_map={navigation.on_open_map}
        />
      ))}
    </RelationshipGroup>
  );
};

export default LocationsRequireSection;
