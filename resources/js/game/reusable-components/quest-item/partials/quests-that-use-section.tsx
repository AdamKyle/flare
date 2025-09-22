import React from 'react';

import QuestRows from './quest-rows';
import Section from '../../viewable-sections/section';
import QuestsThatUseSectionProps from '../types/partials/quest-that-use-section-props';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';

const QuestsThatUseSection = ({
  item,
  showSeparator,
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
  } else if (
    item.required_quest &&
    item.required_quest.npc &&
    item.required_quest.map
  ) {
    lead = (
      <Alert variant={AlertVariant.INFO}>
        The NPC {item.required_quest.npc} lives on this map:{' '}
        {item.required_quest.map}.
      </Alert>
    );
  } else if (list.length === 1 && list[0].npc && list[0].map) {
    lead = (
      <Alert variant={AlertVariant.INFO}>
        The NPC {list[0].npc} lives on this map: {list[0].map}.
      </Alert>
    );
  }

  return (
    <Section
      title="Quests That Use This Item"
      showSeparator={showSeparator}
      lead={lead}
    >
      {item.required_quest ? (
        <QuestRows
          heading="Used In Quest"
          name={item.required_quest.name}
          npc={item.required_quest.npc}
          map={item.required_quest.map}
        />
      ) : null}

      {list.length > 0
        ? list.map((requiredQuest) => (
            <QuestRows
              key={`required-quest-${requiredQuest.id}`}
              heading="Used In Quest"
              name={requiredQuest.name}
              npc={requiredQuest.npc}
              map={requiredQuest.map}
            />
          ))
        : null}
    </Section>
  );
};

export default QuestsThatUseSection;
