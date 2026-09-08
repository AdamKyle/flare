import React, { ReactNode } from 'react';

import QuestStoryPanel from './quest-story-panel';
import QuestDetailProps from '../types/quest-detail-props';

import PillTabs from 'ui/tabs/pill-tabs';

const QuestStorySection = ({ quest }: QuestDetailProps): ReactNode => {
  const hasBefore = Boolean(quest.story.before_completion_markdown);
  const hasAfter = Boolean(quest.story.after_completion_markdown);

  if (!hasBefore && !hasAfter) {
    return null;
  }

  const beforeTab = {
    label: 'Before Completion',
    component: QuestStoryPanel,
    props: { markdown: quest.story.before_completion_markdown },
  } as const;

  const afterTab = {
    label: 'After Completion',
    component: QuestStoryPanel,
    props: { markdown: quest.story.after_completion_markdown },
  } as const;

  const tabs = [
    ...(hasBefore ? [beforeTab] : []),
    ...(hasAfter ? [afterTab] : []),
  ];

  return (
    <div>
      <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
        Story
      </h2>
      <PillTabs tabs={tabs} ariaLabel="Quest story" />
    </div>
  );
};

export default QuestStorySection;
