import React from 'react';

import FactualLink from './factual-link';
import DefinitionRow from '../../viewable-sections/definition-row';
import InfoAlerts from '../../viewable-sections/info-alert';
import InfoLabel from '../../viewable-sections/info-label';
import Section from '../../viewable-sections/section';
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
  const rowLabel = isPlural ? 'Reward Location(s)' : 'Reward Location';

  return (
    <Section
      title={sectionTitle}
      showSeparator={showSeparator}
      lead={
        <InfoAlerts
          messages={[
            'When you visit this location by teleporting or simply moving to it, you will be instantly rewarded with this item.',
          ]}
        />
      }
    >
      {rewardLocations.map((rewardLocation) => (
        <React.Fragment key={`reward-location-${rewardLocation.id}`}>
          <DefinitionRow
            left={<InfoLabel label={rowLabel} />}
            right={
              <FactualLink
                id={rewardLocation.id}
                label={rewardLocation.name}
                on_click={navigation.on_open_location}
              />
            }
          />
          <DefinitionRow
            left={<InfoLabel label="While On Map" />}
            right={
              <FactualLink
                id={rewardLocation.game_map.id}
                label={rewardLocation.game_map.name}
                on_click={navigation.on_open_map}
              />
            }
          />
        </React.Fragment>
      ))}
    </Section>
  );
};

export default RewardLocationsSection;
