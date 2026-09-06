import React from 'react';

import RelationshipGroup from './relationship-group';
import LocationCard from '../../location/components/location-card';
import DropSectionProps from '../types/partials/drop-section-props';

const DropSection = ({ item, showSeparator, navigation }: DropSectionProps) => {
  if (item.drop_location == null) {
    return null;
  }

  return (
    <RelationshipGroup title="Drop" show_separator={showSeparator}>
      <LocationCard
        location_id={item.drop_location.id}
        name={item.drop_location.name}
        game_map_name={item.drop_location.game_map.name}
        on_open_location={navigation.on_open_location}
      />
    </RelationshipGroup>
  );
};

export default DropSection;
