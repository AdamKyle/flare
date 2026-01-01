import React from 'react';

import { getEventTypeName } from '../enums/EventType';
import { useFlipCard } from '../hooks/use-flip-card';
import EventTypeProps from '../types/announcement-types/event-type-props';

import AnimatedCard from 'ui/cards/animated/card-flip/animated-card';
import CardBack from 'ui/cards/animated/card-flip/card-back';
import CardFront from 'ui/cards/animated/card-flip/card-front';
import Card from 'ui/cards/card';

const PurgatorySmithsHouseEvent = ({ announcement }: EventTypeProps) => {
  const { flippedCardKey, handleToggleCard } = useFlipCard();

  const basePath: string = import.meta.env.VITE_BASE_IMAGE_URL;
  const weeklyFactionPoints: string = `${basePath}/event-images/weekly-faction-points.png`;

  return (
    <Card>
      <div className="relative w-full overflow-hidden rounded-tl-md rounded-tr-md border-1 border-b-gray-500 dark:border-gray-700">
        <img
          src={weeklyFactionPoints}
          alt="Purgatory Smith's House overview"
          className="h-40 w-full object-cover sm:h-56 md:h-64"
        />

        <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent" />

        <div className="absolute inset-0 flex items-center justify-center">
          <h4 className="rounded-md bg-black/70 px-6 py-3 text-lg font-semibold text-white shadow-lg sm:text-2xl md:text-3xl">
            {getEventTypeName(announcement.event.type)}
          </h4>
        </div>
      </div>

      <div className="px-4 py-6">
        <div className="flex flex-col gap-4">
          <div className="text-center">
            <p className="mb-2 text-sm text-gray-800 dark:text-gray-100">
              <span className="font-semibold">Ends At:</span>{' '}
              {announcement.expires_at_formatted}
            </p>
            <p className="mx-auto max-w-2xl text-sm leading-relaxed text-gray-700 dark:text-gray-200">
              Step into Purgatory Smith&apos;s House to kick off the true
              end-game grind. The monsters are stronger, the stakes are higher,
              and the payouts are better—three currencies, plus real chances at
              uniques and mythics. Sometimes, you&apos;ll even trigger a rare
              surge that blasts a global message and supercharges the drops.
            </p>
            <p className="mx-auto mt-4 max-w-2xl border-t border-gray-200 pt-2 text-xs text-gray-600 dark:border-gray-700 dark:text-gray-300">
              Click a card to flip it and learn how to farm this location well.
            </p>
          </div>

          <div className="mx-auto w-full max-w-5xl md:max-w-3xl">
            <div className="grid gap-4 md:grid-cols-3">
              <AnimatedCard
                aria_label="Toggle details for End-Game Gearing"
                is_flipped={flippedCardKey === 'end_game_gearing'}
                on_click_card={() => handleToggleCard('end_game_gearing')}
              >
                <CardFront>
                  <h4 className="mb-2 flex items-center justify-center text-sm font-semibold">
                    <i className="ra ra-gem mr-2 text-lg" />
                    End-Game Gearing
                  </h4>
                  <p className="text-xs leading-relaxed text-gray-700 dark:text-gray-200">
                    Purgatory Smith&apos;s House is your bridge into true
                    end-game. Creatures hit harder and punish mistakes, so gear
                    matters. Clear rooms here to begin chasing Purgatory Chains
                    upgrades today, right now.
                  </p>
                </CardFront>

                <CardBack
                  link_title="view"
                  on_click_link={() => console.log('view', 'End-Game Gearing')}
                >
                  <h4 className="mb-2 flex items-center justify-center text-sm font-semibold">
                    <i className="ra ra-gem mr-2 text-lg" />
                    End-Game Gearing
                  </h4>
                  <p className="text-xs leading-relaxed">
                    Smith&apos;s House starts the late-game grind. The monsters
                    are brutal, so arrive fully upgraded. Farm here to bankroll
                    Alchemy and work toward the Purgatory Chains set.
                  </p>
                </CardBack>
              </AnimatedCard>

              <AnimatedCard
                aria_label="Toggle details for Three Currencies"
                is_flipped={flippedCardKey === 'three_currencies'}
                on_click_card={() => handleToggleCard('three_currencies')}
              >
                <CardFront>
                  <h4 className="mb-2 flex items-center justify-center text-sm font-semibold">
                    <i className="ra ra-scroll-unfurled mr-2 text-lg" />
                    Three Currencies
                  </h4>
                  <p className="text-xs leading-relaxed text-gray-700 dark:text-gray-200">
                    This house pays out three currencies at once: Gold Dust,
                    Shards, and Copper Coins. Dust and Shards feed Alchemy
                    power, while Copper Coins unlock systems like Reincarnation,
                    gems, and trinkets.
                  </p>
                </CardFront>

                <CardBack
                  link_title="view"
                  on_click_link={() => console.log('view', 'Three Currencies')}
                >
                  <h4 className="mb-2 flex items-center justify-center text-sm font-semibold">
                    <i className="ra ra-scroll-unfurled mr-2 text-lg" />
                    Three Currencies
                  </h4>
                  <p className="text-xs leading-relaxed">
                    Gold Dust, Shards, and Copper Coins drop together here.
                    Dust/Shards fuel Alchemy, while Coins drive Purgatory
                    systems. Stack runs and convert currency into long-term
                    power.
                  </p>
                </CardBack>
              </AnimatedCard>

              <AnimatedCard
                aria_label="Toggle details for Drops And Surge Event"
                is_flipped={flippedCardKey === 'drops_and_surge'}
                on_click_card={() => handleToggleCard('drops_and_surge')}
              >
                <CardFront>
                  <h4 className="mb-2 flex items-center justify-center text-sm font-semibold">
                    <i className="ra ra-users mr-2 text-lg" />
                    Drops And Surge
                  </h4>
                  <p className="text-xs leading-relaxed text-gray-700 dark:text-gray-200">
                    Every kill gives steady payouts, but a rare surge can also
                    trigger. When it happens, currency rains, item odds jump,
                    and a global message pulls everyone into the house fast.
                  </p>
                </CardFront>

                <CardBack
                  link_title="view"
                  on_click_link={() => console.log('view', 'Drops And Surge')}
                >
                  <h4 className="mb-2 flex items-center justify-center text-sm font-semibold">
                    <i className="ra ra-users mr-2 text-lg" />
                    Drops And Surge
                  </h4>
                  <p className="text-xs leading-relaxed">
                    Drops include 1–1,000 of each currency per kill. Uniques
                    roll in the first half, mythics in the second. A rare surge
                    boosts rewards and blasts a global message.
                  </p>
                </CardBack>
              </AnimatedCard>
            </div>

            <div className="mt-6">
              <dl className="mx-auto max-w-2xl space-y-4 text-left text-sm text-gray-700 dark:text-gray-200">
                <div>
                  <dt className="font-semibold">Where is this event?</dt>
                  <dd className="mt-1">
                    Purgatory Smith&apos;s House is located in Purgatory and is
                    designed to kick off late-game progression where gear
                    matters more and monsters hit much harder.
                  </dd>
                </div>

                <div>
                  <dt className="font-semibold">What should I focus on?</dt>
                  <dd className="mt-1">
                    Farm the three-currency drops (Gold Dust, Shards, and Copper
                    Coins) while pushing your gearing forward toward the
                    Purgatory Chains set—especially if you don&apos;t already
                    have higher-tier event-map gear.
                  </dd>
                </div>

                <div>
                  <dt className="font-semibold">What are the drop rules?</dt>
                  <dd className="mt-1">
                    Normal drops include 1–1,000 of each currency, with unique
                    and mythic rolls based on which half of the monster list
                    you&apos;re fighting. A rare 1-in-1,000 trigger boosts
                    currency to 1–5,000 and improves the unique/mythic odds,
                    then sends a global message to draw players in.
                  </dd>
                </div>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </Card>
  );
};

export default PurgatorySmithsHouseEvent;
