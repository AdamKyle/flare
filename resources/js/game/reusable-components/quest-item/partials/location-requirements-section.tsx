import React from 'react';

import LocationRow from './location-row';
import Section from '../../viewable-sections/section';
import LocationRequirementsSectionProps from '../types/partials/location-requirements-section-props';

const LocationsRequireSection = ({
  item,
  showSeparator,
}: LocationRequirementsSectionProps) => {
  const requiredLocations = item.required_locations || [];

  if (requiredLocations.length === 0) {
    return null;
  }

  return (
    <Section
      title="Locations That Require This Item"
      showSeparator={showSeparator}
    >
      {requiredLocations.map((requiredLocation) => (
        <LocationRow
          key={`required-location-${requiredLocation.id}`}
          heading="Required Location"
          name={requiredLocation.name}
          map={requiredLocation.map}
        />
      ))}
    </Section>
  );
};

export default LocationsRequireSection;
