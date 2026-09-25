import React, { ReactNode, useEffect, useRef, useState } from 'react';

import { useCompleteOnboarding } from './api/hooks/use-complete-onboarding';
import OnboardingNavigation from './components/onboarding-navigation';
import OnboardingSlidePanel from './components/onboarding-slide-panel';
import OnboardingSlidePlaceholder from './components/onboarding-slide-placeholder';
import { buildOnboardingSlides } from './data/onboarding-slides';
import OnboardingScreenProps from './types/onboarding-screen-props';

const OnboardingScreen = ({
  character_id: characterId,
}: OnboardingScreenProps): ReactNode => {
  const { loading, error, complete } = useCompleteOnboarding(characterId);

  const [slides] = useState(buildOnboardingSlides);
  const [currentIndex, setCurrentIndex] = useState(0);

  const headingRef = useRef<HTMLHeadingElement | null>(null);

  const isFirst = currentIndex === 0;
  const isLast = currentIndex === slides.length - 1;
  const currentSlide = slides[currentIndex];

  const handleBackClick = (): void => {
    setCurrentIndex((index) => Math.max(0, index - 1));
  };

  const handleNextClick = (): void => {
    setCurrentIndex((index) => Math.min(slides.length - 1, index + 1));
  };

  const handleFinishClick = async (): Promise<void> => {
    await complete();
  };

  useEffect(() => {
    headingRef.current?.focus({ preventScroll: true });
  }, [currentIndex]);

  return (
    <div className="flex h-full min-h-0 w-full flex-col overflow-hidden md:grid md:grid-cols-3 md:grid-rows-1">
      <div className="h-28 shrink-0 sm:h-40 md:col-span-1 md:h-full">
        <OnboardingSlidePlaceholder />
      </div>

      <div className="flex min-h-0 flex-1 flex-col md:col-span-2">
        <OnboardingSlidePanel slide={currentSlide} heading_ref={headingRef} />

        <OnboardingNavigation
          step={currentIndex + 1}
          total={slides.length}
          is_first={isFirst}
          is_last={isLast}
          submitting={loading}
          submit_error={error}
          on_back={handleBackClick}
          on_next={handleNextClick}
          on_finish={handleFinishClick}
        />
      </div>
    </div>
  );
};

export default OnboardingScreen;
