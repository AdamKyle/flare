import React from 'react';
import ReactDOM from 'react-dom/client';

import ManageGuideQuest from './manage-guide-quest';

const rootNode = document.getElementById('guide-quest-editor');

if (rootNode) {
  const idAttr = rootNode.getAttribute('data-guide-quest-id');
  const parsed = idAttr ? Number(idAttr) : NaN;
  const guide_quest_id = Number.isNaN(parsed) ? 0 : parsed;

  const root = ReactDOM.createRoot(rootNode as HTMLElement);

  root.render(<ManageGuideQuest guide_quest_id={guide_quest_id} />);
}
