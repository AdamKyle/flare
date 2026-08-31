import React from 'react';

import LocationRow from './location-row';
import RelationshipGroup from './relationship-group';
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
        <LocationRow
          key={`reward-location-${rewardLocation.id}`}
          heading="Reward at location"
          location={rewardLocation}
          on_open_location={navigation.on_open_location}
          on_open_map={navigation.on_open_map}
        />
      ))}
    </RelationshipGroup>
  );
};

export default RewardLocationsSection;
