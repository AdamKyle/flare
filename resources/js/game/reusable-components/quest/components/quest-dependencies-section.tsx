import React, { ReactNode } from 'react';

import FactualLink from '../../quest-item/partials/factual-link';
import QuestDetailProps from '../types/quest-detail-props';

import Card from 'ui/cards/card';
import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const QuestDependenciesSection = ({
  quest,
  navigation,
}: QuestDetailProps): ReactNode => {
  const { structure } = quest;

  const renderParentQuest = (): ReactNode => {
    if (!structure.parent_quest) {
      return 'None';
    }

    return (
      <FactualLink
        id={structure.parent_quest.id}
        label={structure.parent_quest.name}
        on_click={navigation?.on_open_quest}
      />
    );
  };

  const renderChildQuests = (): ReactNode => {
    if (structure.child_quests.length === 0) {
      return 'None';
    }

    return (
      <ul className="space-y-1">
        {structure.child_quests.map((child) => (
          <li key={child.id}>
            <FactualLink
              id={child.id}
              label={child.name}
              on_click={navigation?.on_open_quest}
            />
          </li>
        ))}
      </ul>
    );
  };

  const renderRequiredQuest = (): ReactNode => {
    if (!structure.required_quest) {
      return 'None';
    }

    return (
      <FactualLink
        id={structure.required_quest.id}
        label={structure.required_quest.name}
        on_click={navigation?.on_open_quest}
      />
    );
  };

  const renderRequiredQuestChain = (): ReactNode => {
    if (structure.required_quest_chain.length === 0) {
      return 'None';
    }

    return (
      <ol className="list-decimal space-y-1 pl-4">
        {structure.required_quest_chain.map((required) => (
          <li key={required.id}>
            <FactualLink
              id={required.id}
              label={required.name}
              on_click={navigation?.on_open_quest}
            />
          </li>
        ))}
      </ol>
    );
  };

  return (
    <Card>
      <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
        Structure &amp; Dependencies
      </h2>
      <Dl>
        <Dt>Parent Quest</Dt>
        <Dd>{renderParentQuest()}</Dd>
        <Dt>Child Quests</Dt>
        <Dd>{renderChildQuests()}</Dd>
        <Dt>Required Quest</Dt>
        <Dd>{renderRequiredQuest()}</Dd>
        <Dt>Required Quest Chain</Dt>
        <Dd>{renderRequiredQuestChain()}</Dd>
      </Dl>
    </Card>
  );
};

export default QuestDependenciesSection;
