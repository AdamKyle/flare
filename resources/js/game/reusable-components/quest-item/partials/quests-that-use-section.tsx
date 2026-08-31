import React from 'react';

import QuestRows from './quest-rows';
import RelationshipGroup from './relationship-group';
import QuestsThatUseSectionProps from '../types/partials/quest-that-use-section-props';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';

const QuestsThatUseSection = ({
  item,
  showSeparator,
  navigation,
}: QuestsThatUseSectionProps) => {
  const single = item.required_quest != null;
  const list = item.required_quests || [];
  const total = (single ? 1 : 0) + list.length;

  if (total === 0) {
    return null;
  }

  let lead: React.ReactNode | null = null;

  if (total > 1) {
    lead = (
      <Alert variant={AlertVariant.INFO}>
        This quest item is used in the following quests as a required item.
      </Alert>
    );
  } else if (item.required_quest && item.required_quest.npc) {
    lead = (
      <Alert variant={AlertVariant.INFO}>
        The NPC {item.required_quest.npc.name} lives on this map:{' '}
        {item.required_quest.npc.game_map.name}.
      </Alert>
    );
  } else if (list.length === 1 && list[0].npc) {
    lead = (
      <Alert variant={AlertVariant.INFO}>
        The NPC {list[0].npc.name} lives on this map:{' '}
        {list[0].npc.game_map.name}.
      </Alert>
    );
  }

  return (
    <RelationshipGroup
      title="Quests That Use This Item"
      show_separator={showSeparator}
      lead={lead}
    >
      {item.required_quest ? (
        <QuestRows
          heading="Required by quest"
          quest={item.required_quest}
          on_open_quest={navigation.on_open_quest}
          on_open_npc={navigation.on_open_npc}
          on_open_map={navigation.on_open_map}
        />
      ) : null}

      {list.length > 0
        ? list.map((requiredQuest) => (
            <QuestRows
              key={`required-quest-${requiredQuest.id}`}
              heading="Required by quest"
              quest={requiredQuest}
              on_open_quest={navigation.on_open_quest}
              on_open_npc={navigation.on_open_npc}
              on_open_map={navigation.on_open_map}
            />
          ))
        : null}
    </RelationshipGroup>
  );
};

export default QuestsThatUseSection;
