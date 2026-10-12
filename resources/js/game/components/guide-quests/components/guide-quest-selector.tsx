import React, { ReactNode } from 'react';

import GuideQuestSelectorProps from './types/guide-quest-selector-props';

import Dropdown from 'ui/drop-down/drop-down';

const GuideQuestSelector = ({
  quests,
  selected_quest_id: selectedQuestId,
  on_select: onSelect,
}: GuideQuestSelectorProps): ReactNode => {
  if (quests.length <= 1) {
    return null;
  }

  const items = quests.map((quest) => ({
    label: quest.name,
    value: quest.id,
  }));

  return (
    <div className="mb-4">
      <label id="guide-quest-selection-label" className="mb-1 block">
        Available Guide Quest
      </label>
      <Dropdown
        items={items}
        pre_selected_item={items.find((item) => item.value === selectedQuestId)}
        aria_labelled_by="guide-quest-selection-label"
        on_select={(item) => onSelect(Number(item.value))}
      />
    </div>
  );
};

export default GuideQuestSelector;
