import React from 'react';

import LocationRow from './location-row';
import Section from '../../viewable-sections/section';
import MonsterDropsSectionProps from '../types/partials/monster-drop-section-props';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';

const MonsterDropsSection = ({
  item,
  showSeparator,
}: MonsterDropsSectionProps) => {
  if (item.required_monster == null) {
    return null;
  }

  const lead = (
    <Alert variant={AlertVariant.INFO}>
      The monster {item.required_monster.name} has a chance to drop this quest
      item when defeated in manual fights or during automated exploration.
    </Alert>
  );

  return (
    <Section
      title="Monster That Drops It"
      showSeparator={showSeparator}
      lead={lead}
    >
      <LocationRow
        heading="Monster"
        name={item.required_monster.name}
        map={item.required_monster.map}
      />
    </Section>
  );
};

export default MonsterDropsSection;
