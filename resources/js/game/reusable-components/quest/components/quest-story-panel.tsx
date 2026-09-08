import React, { ReactNode } from 'react';
import ReactMarkdown from 'react-markdown';

import QuestStoryPanelProps from '../types/quest-story-panel-props';
import { normalizeQuestStoryMarkdown } from '../utils/normalize-quest-story-markdown';

/**
 * Legacy `<br>` tokens are normalized for display without changing persisted text.
 */
const QuestStoryPanel = ({ markdown }: QuestStoryPanelProps): ReactNode => {
  if (!markdown) {
    return null;
  }

  return (
    <div className="border-glacier-200 dark:border-glacier-800 max-h-[400px] overflow-y-auto rounded-md border p-3">
      <div className="prose prose-sm sm:prose-base dark:prose-invert max-w-none break-words">
        <ReactMarkdown>{normalizeQuestStoryMarkdown(markdown)}</ReactMarkdown>
      </div>
    </div>
  );
};

export default QuestStoryPanel;
