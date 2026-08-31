import React, { ReactNode } from 'react';

import QuestStoryPanel from './quest-story-panel';
import QuestDetailProps from '../types/quest-detail-props';

import PillTabs from 'ui/tabs/pill-tabs';

/**
 * Quest story presentation: Before/After Completion tabs, each a bounded,
 * internally-scrollable prose panel (max 400px), rather than one long wall
 * of text.
 */
const QuestStorySection = ({ quest }: QuestDetailProps): ReactNode => {
  const tabs = [
    {
      label: 'Before Completion',
      component: QuestStoryPanel,
      props: { markdown: quest.story.before_completion_markdown },
    },
    {
      label: 'After Completion',
      component: QuestStoryPanel,
      props: { markdown: quest.story.after_completion_markdown },
    },
  ] as const;

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
