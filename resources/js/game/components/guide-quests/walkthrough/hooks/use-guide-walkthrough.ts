import { useContext } from 'react';

import { GuideWalkthroughContext } from '../context/guide-walkthrough-context';

export const useGuideWalkthrough = () => {
  const context = useContext(GuideWalkthroughContext);

  if (!context) {
    throw new Error(
      'useGuideWalkthrough must be used within GuideWalkthroughProvider.'
    );
  }

  return context;
};
