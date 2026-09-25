import { ReactNode } from 'react';

import EventFaqEntry from './event-faq-entry';
import EventFeatureCard from './event-feature-card';

export default interface EventDetailLayoutProps {
  hero_image_src: string;
  hero_alt: string;
  title: string;
  ends_at_formatted: string;
  intro: ReactNode;
  cards: EventFeatureCard[];
  faq: EventFaqEntry[];
}
