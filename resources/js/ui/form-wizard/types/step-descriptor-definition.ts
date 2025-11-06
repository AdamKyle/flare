import { ReactNode } from 'react';

export default interface StepDescriptorDefinition {
  id: string;
  title: string;
  element: ReactNode;
}
