import React from 'react';

import LocationRow from './location-row';
import Section from '../../viewable-sections/section';
import DropSectionProps from '../types/partials/drop-section-props';

const DropSection = ({ item, showSeparator }: DropSectionProps) => {
  if (item.drop_location == null) {
    return null;
  }

  return (
    <Section title="Drop" showSeparator={showSeparator}>
      <LocationRow
        heading="Drops At"
        name={item.drop_location.name}
        map={item.drop_location.map}
      />
    </Section>
  );
};

export default DropSection;
