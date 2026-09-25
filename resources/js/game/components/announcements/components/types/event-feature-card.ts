import { ReactNode } from 'react';

export default interface EventFeatureCard {
  key: string;
  aria_label: string;
  icon_class: string;
  title: string;
  front_body: ReactNode;
  back_body: ReactNode;
}
