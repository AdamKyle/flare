import React, { ReactNode } from 'react';

import OnboardingSlidePanelProps from '../types/onboarding-slide-panel-props';

const OnboardingSlidePanel = ({
  slide,
  heading_ref: headingRef,
}: OnboardingSlidePanelProps): ReactNode => {
  return (
    <div className="flex min-h-0 flex-1 flex-col overflow-y-auto px-4 py-4 md:px-8 md:py-6">
      <div className="my-auto flex w-full max-w-2xl flex-col gap-3">
        <h2
          ref={headingRef}
          tabIndex={-1}
          className="text-xl font-bold text-black focus:outline-none md:text-2xl dark:text-white"
        >
          {slide.title}
        </h2>

        <div className="space-y-3 text-sm text-gray-700 md:text-base dark:text-gray-300">
          {slide.body}
        </div>
      </div>
    </div>
  );
};

export default OnboardingSlidePanel;
