import { ReactNode } from 'react';

export default interface OnboardingSlideDefinition {
  id: string;
  title: string;
  body: ReactNode;
}
