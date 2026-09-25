import React, { ReactNode } from 'react';

import { useFlipCard } from '../hooks/use-flip-card';
import EventDetailLayoutProps from './types/event-detail-layout-props';

import AnimatedCard from 'ui/cards/animated/card-flip/animated-card';
import CardBack from 'ui/cards/animated/card-flip/card-back';
import CardFront from 'ui/cards/animated/card-flip/card-front';
import Card from 'ui/cards/card';

const EventDetailLayout = ({
  hero_image_src: heroImageSrc,
  hero_alt: heroAlt,
  title,
  ends_at_formatted: endsAtFormatted,
  intro,
  cards,
  faq,
}: EventDetailLayoutProps): ReactNode => {
  const { flippedCardKey, handleToggleCard } = useFlipCard();

  return (
    <Card>
      <div className="relative w-full overflow-hidden rounded-tl-md rounded-tr-md border-1 border-b-gray-500 dark:border-gray-700">
        <img
          src={heroImageSrc}
          alt={heroAlt}
          className="h-40 w-full object-cover sm:h-56 md:h-64"
        />

        <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent" />

        <div className="absolute inset-0 flex items-center justify-center">
          <h4 className="rounded-md bg-black/70 px-6 py-3 text-lg font-semibold text-white shadow-lg sm:text-2xl md:text-3xl">
            {title}
          </h4>
        </div>
      </div>

      <div className="px-4 py-6">
        <div className="flex flex-col gap-4">
          <div className="text-center">
            <p className="mb-2 text-sm text-gray-800 dark:text-gray-100">
              <span className="font-semibold">Ends At:</span> {endsAtFormatted}
            </p>
            <div className="mx-auto max-w-2xl text-sm leading-relaxed text-gray-700 dark:text-gray-200">
              {intro}
            </div>
            <p className="mx-auto mt-4 max-w-2xl border-t border-gray-200 pt-2 text-xs text-gray-600 dark:border-gray-700 dark:text-gray-300">
              Select a card to flip it and learn more.
            </p>
          </div>

          <div className="mx-auto w-full max-w-5xl md:max-w-3xl">
            <div className="grid gap-4 md:grid-cols-3">
              {cards.map((card) => (
                <AnimatedCard
                  key={card.key}
                  aria_label={card.aria_label}
                  is_flipped={flippedCardKey === card.key}
                  on_click_card={() => handleToggleCard(card.key)}
                >
                  <CardFront>
                    <h4 className="mb-2 flex items-center justify-center text-sm font-semibold">
                      <i
                        className={`${card.icon_class} mr-2 text-lg`}
                        aria-hidden="true"
                      />
                      {card.title}
                    </h4>
                    <p className="text-xs leading-relaxed text-gray-700 dark:text-gray-200">
                      {card.front_body}
                    </p>
                  </CardFront>

                  <CardBack>
                    <h4 className="mb-2 flex items-center justify-center text-sm font-semibold">
                      <i
                        className={`${card.icon_class} mr-2 text-lg`}
                        aria-hidden="true"
                      />
                      {card.title}
                    </h4>
                    <p className="text-xs leading-relaxed">{card.back_body}</p>
                  </CardBack>
                </AnimatedCard>
              ))}
            </div>

            {faq.length > 0 && (
              <div className="mt-6">
                <dl className="mx-auto max-w-2xl space-y-4 text-left text-sm text-gray-700 dark:text-gray-200">
                  {faq.map((entry) => (
                    <div key={entry.question}>
                      <dt className="font-semibold">{entry.question}</dt>
                      <dd className="mt-1">{entry.answer}</dd>
                    </div>
                  ))}
                </dl>
              </div>
            )}
          </div>
        </div>
      </div>
    </Card>
  );
};

export default EventDetailLayout;
