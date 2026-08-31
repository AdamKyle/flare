import React from 'react';

import LocationRow from './location-row';
import RelationshipGroup from './relationship-group';
import DropSectionProps from '../types/partials/drop-section-props';

const DropSection = ({ item, showSeparator, navigation }: DropSectionProps) => {
  if (item.drop_location == null) {
    return null;
  }

  return (
    <RelationshipGroup title="Drop" show_separator={showSeparator}>
      <LocationRow
        heading="Drops at location"
        location={item.drop_location}
        on_open_location={navigation.on_open_location}
        on_open_map={navigation.on_open_map}
      />
    </RelationshipGroup>
  );
};

export default DropSection;
