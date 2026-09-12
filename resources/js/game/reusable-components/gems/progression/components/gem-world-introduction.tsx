import React, { ReactNode, useState } from 'react';

import GemWorldIntroductionProps from './types/gem-world-introduction-props';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LoadingButton from 'ui/buttons/loading-button';

interface IntroductionPage {
  title: string;
  paragraphs: string[];
}

const PAGES: IntroductionPage[] = [
  {
    title: 'What Are Gem Worlds?',
    paragraphs: [
      'Map and Location Gems can generate their own Gem World: a special version of that Map or Location with its own Monsters and its own progression.',
      "Entry is contextual — while standing on a Location with its own Gem World, only that Location Gem World can be entered. Away from such a Location, only the current Map's Gem World can be entered.",
    ],
  },
  {
    title: 'Global Progression',
    paragraphs: [
      'Every Gem World profile has a shared Global level from 1 to 100. Every qualifying kill any Character makes inside that Gem World contributes Global XP.',
      'As the Global level rises, the positive/reward effects of that Gem World improve for every Character who benefits from it — not just you.',
    ],
  },
  {
    title: 'Personal Progression',
    paragraphs: [
      'You also have your own Personal level for this exact Gem profile, from 1 to 1000. Personal levels grow your own positive bonuses further.',
      "Past level 100, Personal progression also increases how difficult this Gem World's Monsters are for you specifically, and unlocks rarer end-game item rewards the higher you climb.",
    ],
  },
  {
    title: 'Gem Scrolls',
    paragraphs: [
      'Once you are Personal level 100 or higher, qualifying Monster kills inside a Gem World have a 2% chance to drop a Gem Scroll.',
      'Scrolls come in three families — XP, Currency, and Item — and their quality improves as your Personal level rises.',
    ],
  },
  {
    title: 'Using Gem Scrolls',
    paragraphs: [
      'Gem Scrolls are generated Items that live in your Alchemy Bag. A Scroll becomes bound to the exact Gem profile where you consume it, and its active effect applies only to that profile.',
      'You may enter another Gem World and consume separate Scrolls there — this does not affect a Scroll already active in a different profile.',
      "Once activated, a Scroll's timer counts down in real time and never pauses. Leaving the Gem World does not stop the timer, and returning before it expires makes the still-active effect applicable again. Fill Up extends an active Scroll's duration; Remove cancels it without a refund.",
      "All of your active Scrolls' primary bonuses for one Gem profile share a combined 2000% cap.",
    ],
  },
  {
    title: 'Reward Limits',
    paragraphs: [
      'Currency rewards above your existing caps are lost. Extra Item and Gem Scroll rewards are lost if your Inventory or Alchemy Bag is full when they are earned — there is no overflow storage.',
      'You will always be warned when a reward is lost this way.',
    ],
  },
  {
    title: 'End-Game Rewards',
    paragraphs: [
      'Higher Personal levels unlock a chance at Unique, Mythic, and eventually Cosmic item rewards, and active Item Scrolls grant additional reward opportunities.',
      'At Personal level 700 and beyond, qualifying kills have a small chance to award enhanced equipment with sockets and pre-attached Tier Four Gems.',
    ],
  },
];

const GemWorldIntroduction = ({
  loading,
  error,
  on_acknowledge: onAcknowledge,
}: GemWorldIntroductionProps): ReactNode => {
  const [pageIndex, setPageIndex] = useState(0);

  const isFirstPage = pageIndex === 0;
  const isLastPage = pageIndex === PAGES.length - 1;
  const page = PAGES[pageIndex];

  const handleNext = (): void => {
    setPageIndex((previous) => Math.min(previous + 1, PAGES.length - 1));
  };

  const handleBack = (): void => {
    setPageIndex((previous) => Math.max(previous - 1, 0));
  };

  return (
    <div className="flex flex-col gap-3">
      <span className="text-xs font-medium tracking-wide text-gray-500 uppercase dark:text-gray-400">
        Page {pageIndex + 1} of {PAGES.length}
      </span>
      <h3 className="text-base font-semibold text-gray-800 dark:text-gray-200">
        {page.title}
      </h3>
      <div className="flex flex-col gap-2 text-sm text-gray-700 dark:text-gray-300">
        {page.paragraphs.map((paragraph) => (
          <p key={paragraph}>{paragraph}</p>
        ))}
      </div>

      {error && <Alert variant={AlertVariant.DANGER}>{error}</Alert>}

      <div className="mt-2 flex items-center justify-between gap-2">
        <Button
          label="Back"
          variant={ButtonVariant.PRIMARY}
          disabled={isFirstPage}
          on_click={handleBack}
        />
        {isLastPage ? (
          <LoadingButton
            label="I Understand"
            loading_label="Saving…"
            variant={ButtonVariant.SUCCESS}
            is_loading={loading}
            on_click={onAcknowledge}
          />
        ) : (
          <Button
            label="Next"
            variant={ButtonVariant.PRIMARY}
            on_click={handleNext}
          />
        )}
      </div>
    </div>
  );
};

export default GemWorldIntroduction;
