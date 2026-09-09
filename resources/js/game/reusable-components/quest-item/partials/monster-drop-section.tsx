import React from 'react';

import FactualLink from './factual-link';
import RelationshipGroup from './relationship-group';
import { relationshipRowClassName } from './relationship-row-styles';
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
    <RelationshipGroup
      title={isPlural ? 'Monsters That Drop It' : 'Monster That Drops It'}
      show_separator={showSeparator}
      lead={lead}
    >
      {monsters.map((monster) => (
        <div
          key={`required-monster-${monster.id}`}
          className={relationshipRowClassName}
        >
          <p className="text-glacier-900 dark:text-glacier-900 font-medium">
            <FactualLink
              id={monster.id}
              label={monster.name}
              on_click={navigation.on_open_monster}
            />
          </p>
          <p className="text-glacier-600 dark:text-glacier-800 text-xs">
            While on map
            {' · '}
            <FactualLink
              id={monster.game_map.id}
              label={monster.game_map.name}
              on_click={navigation.on_open_map}
            />
          </p>
        </div>
      ))}
    </RelationshipGroup>
  );
};

export default MonsterDropsSection;
