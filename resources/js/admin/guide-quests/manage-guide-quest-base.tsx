import React from 'react';
import ReactDOM from 'react-dom/client';

import ManageGuideQuest from './manage-guide-quest';

const rootNode = document.getElementById('guide-quest-editor');

if (rootNode) {
  const root = ReactDOM.createRoot(rootNode as HTMLElement);

  root.render(<ManageGuideQuest />);
}
