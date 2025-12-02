import { motion } from 'framer-motion';
import React, { useState } from 'react';

import AnnouncementDetailsProps from './types/announcement-details-props';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LinkButton from 'ui/buttons/link-button';
import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';

const AnnouncementDetails = ({ on_close }: AnnouncementDetailsProps) => {
  const [flippedCardIndex, setFlippedCardIndex] = useState<number | null>(null);

  const infoCardTitles = ['Bonus Rewards', 'Faction Tasks', 'NPC Refresh'];

  const handleCardClick = (cardIndex: number) => {
    if (flippedCardIndex === cardIndex) {
      setFlippedCardIndex(null);

      return;
    }

    setFlippedCardIndex(cardIndex);
  };

  const renderInfoCards = () => {
    return infoCardTitles.map((cardTitle, cardIndex) => {
      const isFlipped = flippedCardIndex === cardIndex;

      return (
        <div
          key={cardTitle}
          className="relative mx-auto h-44 w-full max-w-xs md:h-56"
          style={{ perspective: '1200px' }}
        >
          <button
            type="button"
            onClick={() => handleCardClick(cardIndex)}
            className="group relative block h-full w-full bg-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-gray-900"
            aria-pressed={isFlipped}
            aria-label={`Toggle details for ${cardTitle}`}
          >
            <motion.div
              className="relative z-10 h-full w-full"
              style={{ transformStyle: 'preserve-3d' }}
              animate={{ rotateY: isFlipped ? 180 : 0 }}
              transition={{ duration: 0.45 }}
            >
              <div
                className="absolute inset-0 flex h-full flex-col items-center justify-center rounded-t-xl border-x border-t border-gray-200/80 bg-gray-50 px-4 text-center text-gray-900 shadow-sm transition-shadow group-hover:shadow-md dark:border-gray-600/70 dark:bg-gray-800 dark:text-gray-50"
                style={{
                  backfaceVisibility: 'hidden',
                  WebkitBackfaceVisibility: 'hidden',
                  transform: 'rotateY(0deg)',
                }}
              >
                <h4 className="mb-2 text-sm font-semibold">{cardTitle}</h4>
                <p className="text-xs leading-relaxed text-gray-700 dark:text-gray-200">
                  Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed
                  non risus. Integer posuere erat a ante venenatis dapibus.
                </p>
              </div>

              <div
                className="absolute inset-0 flex h-full flex-col items-center justify-between rounded-t-xl border-x border-t border-gray-900/60 bg-gray-900 px-4 py-4 text-center text-gray-50 shadow-sm transition-shadow group-hover:shadow-md dark:border-gray-700/80 dark:bg-gray-700"
                style={{
                  backfaceVisibility: 'hidden',
                  WebkitBackfaceVisibility: 'hidden',
                  transform: 'rotateY(180deg)',
                }}
              >
                <div className="px-1">
                  <h4 className="mb-2 text-sm font-semibold">{cardTitle}</h4>
                  <p className="text-xs leading-relaxed">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed
                    non risus. Suspendisse lectus tortor, dignissim sit amet,
                    adipiscing nec, ultricies sed, dolor.
                  </p>
                </div>

                <div className="mt-3 flex justify-center pb-1">
                  <LinkButton
                    label="view"
                    variant={ButtonVariant.PRIMARY}
                    on_click={() => console.log('view', cardTitle)}
                  />
                </div>
              </div>
            </motion.div>
          </button>
        </div>
      );
    });
  };

  return (
    <ContainerWithTitle
      manageSectionVisibility={on_close}
      title="Announcement Details"
    >
      <Card>
        <div className="flex flex-col gap-4">
          <div className="text-center">
            <h3 className="mb-2 text-xl font-semibold text-gray-900 dark:text-gray-50">
              Weekly Faction Loyalty Event
            </h3>
            <p className="mb-2 text-sm text-gray-800 dark:text-gray-100">
              <span className="font-semibold">Ends At:</span> Today&apos;s date
            </p>
            <p className="mx-auto max-w-2xl text-sm leading-relaxed text-gray-700 dark:text-gray-200">
              Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer
              nec odio. Praesent libero. Sed cursus ante dapibus diam. Sed nisi.
              Nulla quis sem at nibh elementum imperdiet. Duis sagittis ipsum.
            </p>
          </div>

          <div className="mx-auto w-full max-w-5xl md:max-w-3xl">
            <div className="grid gap-4 md:grid-cols-3">{renderInfoCards()}</div>
          </div>
        </div>
      </Card>
    </ContainerWithTitle>
  );
};

export default AnnouncementDetails;
