import React, { ReactNode } from 'react';

import DelveQuestItemsSectionProps from './types/delve-quest-items-section-props';
import ExplorationDetailSection from '../../exploration/components/exploration-detail-section';
import DelveQuestItemDefinition from '../api/definitions/delve-quest-item-definition';
import { resolveDelveQuestItemOwnershipLabel } from '../utils/resolve-delve-quest-item-ownership-label';

const DelveQuestItemsSection = ({
  quest_items: questItems,
  on_view_quest_item: onViewQuestItem,
}: DelveQuestItemsSectionProps): ReactNode => {
  const renderQuestItem = (questItem: DelveQuestItemDefinition) => (
    <li
      key={questItem.id}
      className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between"
    >
      <button
        type="button"
        onClick={() => onViewQuestItem(questItem.id)}
        aria-label={`View ${questItem.name} details`}
        className="text-danube-700 hover:text-danube-600 dark:text-danube-200 dark:hover:text-danube-100 decoration-danube-400 dark:decoration-danube-500 focus-visible:ring-danube-400 rounded-sm text-left font-medium underline underline-offset-2 focus:outline-none focus-visible:ring-2"
      >
        {questItem.name}
      </button>
      <span className="text-sm text-gray-700 dark:text-gray-300">
        {resolveDelveQuestItemOwnershipLabel(questItem)}
      </span>
    </li>
  );

  if (questItems.length === 0) {
    return null;
  }

  return (
    <ExplorationDetailSection title="Delve Quest Items">
      <ul className="space-y-2">{questItems.map(renderQuestItem)}</ul>
    </ExplorationDetailSection>
  );
};

export default DelveQuestItemsSection;
