import React, { ReactNode } from 'react';
import ReactMarkdown from 'react-markdown';

import GuideQuestContentSectionProps from './types/guide-quest-content-section-props';
import { GuideQuestContentBlockDefinition } from '../api/definitions/guide-quest-definition';

import Card from 'ui/cards/card';

const GuideQuestContentSection = ({
  title,
  blocks,
}: GuideQuestContentSectionProps): ReactNode => {
  const visibleBlocks = (blocks ?? []).filter(
    (block) =>
      block.content.trim() !== '' ||
      (typeof block.image_url === 'string' && block.image_url !== '')
  );

  const renderImage = (block: GuideQuestContentBlockDefinition): ReactNode => {
    if (typeof block.image_url !== 'string' || block.image_url === '') {
      return null;
    }

    return (
      <img
        src={block.image_url}
        alt=""
        className="w-full rounded-md ring-1 ring-gray-300 md:w-2/5 dark:ring-gray-700"
      />
    );
  };

  const renderBlock = (block: GuideQuestContentBlockDefinition): ReactNode => (
    <div
      key={block.id}
      className="flex flex-col gap-4 md:flex-row md:items-start"
    >
      {renderImage(block)}
      <div className="prose prose-sm sm:prose-base dark:prose-invert max-w-none min-w-0 flex-1 break-words">
        <ReactMarkdown>{block.content}</ReactMarkdown>
      </div>
    </div>
  );

  if (visibleBlocks.length === 0) {
    return null;
  }

  return (
    <Card>
      <h3 className="mb-3 text-lg font-semibold text-gray-900 dark:text-gray-100">
        {title}
      </h3>
      <div className="space-y-6">{visibleBlocks.map(renderBlock)}</div>
    </Card>
  );
};

export default GuideQuestContentSection;
