import React from 'react';

import LocationRow from './location-row';
import Section from '../../viewable-sections/section';
import DropSectionProps from '../types/partials/drop-section-props';

const DropSection = ({ item, showSeparator, navigation }: DropSectionProps) => {
  if (item.drop_location == null) {
    return null;
  }

  return (
    <Section title="Drop" showSeparator={showSeparator}>
      <LocationRow
        heading="Drops At"
        location={item.drop_location}
        on_open_location={navigation.on_open_location}
        on_open_map={navigation.on_open_map}
      />
    </Section>
  );
};

export default DropSection;
