import { RefObject } from 'react';

import OnboardingSlideDefinition from './onboarding-slide-definition';

export default interface OnboardingSlidePanelProps {
  slide: OnboardingSlideDefinition;
  heading_ref: RefObject<HTMLHeadingElement | null>;
}
