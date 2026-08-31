import React from 'react';

import FactualLink from './factual-link';
import DefinitionRow from '../../viewable-sections/definition-row';
import InfoLabel from '../../viewable-sections/info-label';
import Section from '../../viewable-sections/section';
import MonsterDropsSectionProps from '../types/partials/monster-drop-section-props';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';

const MonsterDropsSection = ({
  item,
  showSeparator,
  navigation,
}: MonsterDropsSectionProps) => {
  const monsters = item.required_monsters || [];

  if (monsters.length === 0) {
    return null;
  }

  const isPlural = monsters.length !== 1;

  const lead = (
    <Alert variant={AlertVariant.INFO}>
      {isPlural
        ? 'The following monsters have a chance to drop this quest item when defeated in manual fights or during automated exploration.'
        : `The monster ${monsters[0].name} has a chance to drop this quest item when defeated in manual fights or during automated exploration.`}
    </Alert>
  );

  return (
    <Section
      title={isPlural ? 'Monsters That Drop It' : 'Monster That Drops It'}
      showSeparator={showSeparator}
      lead={lead}
    >
      {monsters.map((monster) => (
        <React.Fragment key={`required-monster-${monster.id}`}>
          <DefinitionRow
            left={<InfoLabel label="Monster" />}
            right={
              <FactualLink
                id={monster.id}
                label={monster.name}
                on_click={navigation.on_open_monster}
              />
            }
          />
          <DefinitionRow
            left={<InfoLabel label="While On Map" />}
            right={
              <FactualLink
                id={monster.game_map.id}
                label={monster.game_map.name}
                on_click={navigation.on_open_map}
              />
            }
          />
        </React.Fragment>
      ))}
    </Section>
  );
};

export default MonsterDropsSection;
