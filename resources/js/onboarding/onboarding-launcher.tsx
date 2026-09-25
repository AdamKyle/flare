import React from 'react';
import ReactDOM from 'react-dom/client';

import OnboardingApp from './onboarding-app';

const rootNode = document.getElementById('onboarding-launcher');

if (!rootNode) {
  throw new Error('The onboarding launcher root element is missing.');
}

const characterId = Number(rootNode.dataset.characterId);

if (!Number.isFinite(characterId) || characterId <= 0) {
  throw new Error('The onboarding launcher requires a valid character id.');
}

ReactDOM.createRoot(rootNode).render(
  <OnboardingApp character_id={characterId} />
);
