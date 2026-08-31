import React, { ReactNode } from 'react';
import ReactMarkdown from 'react-markdown';

import QuestDetailProps from '../types/quest-detail-props';

import Card from 'ui/cards/card';

const QuestStorySection = ({ quest }: QuestDetailProps): ReactNode => {
  const renderBeforeCompletion = (): ReactNode => {
    if (!quest.story.before_completion_markdown) {
      return (
        <p className="text-glacier-500 dark:text-glacier-400 text-sm">None.</p>
      );
    }

    return (
      <div className="text-glacier-700 dark:text-glacier-300 text-sm break-words">
        <ReactMarkdown>{quest.story.before_completion_markdown}</ReactMarkdown>
      </div>
    );
  };

  const renderAfterCompletion = (): ReactNode => {
    if (!quest.story.after_completion_markdown) {
      return (
        <p className="text-glacier-500 dark:text-glacier-400 text-sm">None.</p>
      );
    }

    return (
      <div className="text-glacier-700 dark:text-glacier-300 text-sm break-words">
        <ReactMarkdown>{quest.story.after_completion_markdown}</ReactMarkdown>
      </div>
    );
  };

  return (
    <Card>
      <h2 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
        Story
      </h2>
      <div className="space-y-4">
        <div>
          <h3 className="text-glacier-700 dark:text-glacier-300 text-xs font-semibold tracking-wide uppercase">
            Before Completion
          </h3>
          {renderBeforeCompletion()}
        </div>
        <div>
          <h3 className="text-glacier-700 dark:text-glacier-300 text-xs font-semibold tracking-wide uppercase">
            After Completion
          </h3>
          {renderAfterCompletion()}
        </div>
      </div>
    </Card>
  );
};

export default QuestStorySection;
