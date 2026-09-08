import React from 'react';

import RelationshipGroup from './relationship-group';
import QuestCard from '../../quest/components/quest-card';
import QuestsThatUseSectionProps from '../types/partials/quest-that-use-section-props';
import { QuestIdentityDefinition } from '../types/quest-item-factual-definition';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';

const buildRequiredByContextLabel = (quest: QuestIdentityDefinition): string =>
  quest.game_map
    ? `Required by quest on ${quest.game_map.name}`
    : 'Required by quest';

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
        <QuestCard
          quest_id={item.required_quest.id}
          name={item.required_quest.name}
          npc_name={item.required_quest.npc?.name ?? null}
          context_label={buildRequiredByContextLabel(item.required_quest)}
          on_open_quest={navigation.on_open_quest}
        />
      ) : null}

      {list.length > 0
        ? list.map((requiredQuest) => (
            <QuestCard
              key={`required-quest-${requiredQuest.id}`}
              quest_id={requiredQuest.id}
              name={requiredQuest.name}
              npc_name={requiredQuest.npc?.name ?? null}
              context_label={buildRequiredByContextLabel(requiredQuest)}
              on_open_quest={navigation.on_open_quest}
            />
          ))
        : null}
    </RelationshipGroup>
  );
};

export default QuestsThatUseSection;
