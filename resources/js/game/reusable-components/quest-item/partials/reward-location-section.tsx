import React from 'react';

import RelationshipGroup from './relationship-group';
import LocationCard from '../../location/components/location-card';
import InfoAlerts from '../../viewable-sections/info-alert';
import RewardLocationsSectionProps from '../types/partials/location-reward-section-props';

const RewardLocationsSection = ({
  item,
  showSeparator,
  navigation,
}: RewardLocationsSectionProps) => {
  const rewardLocations = item.reward_locations || [];

  if (rewardLocations.length === 0) {
    return null;
  }

  const isPlural = rewardLocations.length !== 1;
  const sectionTitle = isPlural
    ? 'Locations That Reward for Visiting'
    : 'Location That Rewards for Visiting';

  return (
    <RelationshipGroup
      title={sectionTitle}
      show_separator={showSeparator}
      lead={
        <InfoAlerts
          messages={[
            'When you visit this location by teleporting or simply moving to it, you will be instantly rewarded with this item.',
          ]}
        />
      }
    >
      {rewardLocations.map((rewardLocation) => (
        <LocationCard
          key={`reward-location-${rewardLocation.id}`}
          location_id={rewardLocation.id}
          name={rewardLocation.name}
          game_map_name={rewardLocation.game_map.name}
          on_open_location={navigation.on_open_location}
        />
      ))}
    </RelationshipGroup>
  );
};

export default RewardLocationsSection;
