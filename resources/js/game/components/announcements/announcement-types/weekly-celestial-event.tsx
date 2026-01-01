import React from 'react';

import { getEventTypeName } from '../enums/EventType';
import { useFlipCard } from '../hooks/use-flip-card';
import EventTypeProps from '../types/announcement-types/event-type-props';

import AnimatedCard from 'ui/cards/animated/card-flip/animated-card';
import CardBack from 'ui/cards/animated/card-flip/card-back';
import CardFront from 'ui/cards/animated/card-flip/card-front';
import Card from 'ui/cards/card';

const WeeklyCelestialEvent = ({ announcement }: EventTypeProps) => {
  const { flippedCardKey, handleToggleCard } = useFlipCard();

  const basePath: string = import.meta.env.VITE_BASE_IMAGE_URL;
  const weeklyFactionPoints: string = `${basePath}/event-images/weekly-faction-points.png`;

  return (
    <Card>
      <div className="relative w-full overflow-hidden rounded-tl-md rounded-tr-md border-1 border-b-gray-500 dark:border-gray-700">
        <img
          src={weeklyFactionPoints}
          alt="Weekly Celestials overview"
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
              For the next 24 hours, the gates are cracked open and Celestials
              can spill onto the planes. Any travel—directional movement,
              teleporting, setting sail, or traversing—can trigger a spawn (at a
              high rate), so keep moving, find the target with /PC, then use
              /PCT to jump in and claim the kill for Shards you can spend in
              Alchemy.
            </p>
            <p className="mx-auto mt-4 max-w-2xl border-t border-gray-200 pt-2 text-xs text-gray-600 dark:border-gray-700 dark:text-gray-300">
              Click a card to flip it and learn the core loop of the hunt.
            </p>
          </div>

          <div className="mx-auto w-full max-w-5xl md:max-w-3xl">
            <div className="grid gap-4 md:grid-cols-3">
              <AnimatedCard
                aria_label="Toggle details for Spawn By Moving"
                is_flipped={flippedCardKey === 'bonus_rewards'}
                on_click_card={() => handleToggleCard('bonus_rewards')}
              >
                <CardFront>
                  <h4 className="mb-2 flex items-center justify-center text-sm font-semibold">
                    <i className="ra ra-gem mr-2 text-lg" />
                    Spawn By Moving
                  </h4>
                  <p className="text-xs leading-relaxed text-gray-700 dark:text-gray-200">
                    Every step, teleport, sail, or traverse can spark a
                    Celestial spawn somewhere in the planes. Keep moving, then
                    use /PCT to drop straight into the fight.
                  </p>
                </CardFront>

                <CardBack
                  link_title="view"
                  on_click_link={() => console.log('view', 'Spawn By Moving')}
                >
                  <h4 className="mb-2 flex items-center justify-center text-sm font-semibold">
                    <i className="ra ra-gem mr-2 text-lg" />
                    Spawn By Moving
                  </h4>
                  <p className="text-xs leading-relaxed">
                    Travel in any way—directional moves, teleports, sailing, or
                    traversing—and spawns roll at a high rate. When one appears,
                    use /PCT to teleport to it and strike.
                  </p>
                </CardBack>
              </AnimatedCard>

              <AnimatedCard
                aria_label="Toggle details for Earn Shards"
                is_flipped={flippedCardKey === 'faction_tasks'}
                on_click_card={() => handleToggleCard('faction_tasks')}
              >
                <CardFront>
                  <h4 className="mb-2 flex items-center justify-center text-sm font-semibold">
                    <i className="ra ra-scroll-unfurled mr-2 text-lg" />
                    Earn Shards
                  </h4>
                  <p className="text-xs leading-relaxed text-gray-700 dark:text-gray-200">
                    Be first to kill a Celestial and you earn Shards, a rare
                    event currency. Spend them in Alchemy to convert hunts into
                    lasting, truly godly power.
                  </p>
                </CardFront>

                <CardBack
                  link_title="view"
                  on_click_link={() => console.log('view', 'Earn Shards')}
                >
                  <h4 className="mb-2 flex items-center justify-center text-sm font-semibold">
                    <i className="ra ra-scroll-unfurled mr-2 text-lg" />
                    Earn Shards
                  </h4>
                  <p className="text-xs leading-relaxed">
                    Shards go to whoever lands the first kill, so speed and
                    scouting win. Stockpile Shards and use Alchemy to craft,
                    upgrade, and push your build forward even faster.
                  </p>
                </CardBack>
              </AnimatedCard>

              <AnimatedCard
                aria_label="Toggle details for One-Hit Or It Flees"
                is_flipped={flippedCardKey === 'pledge_help_npcs'}
                on_click_card={() => handleToggleCard('pledge_help_npcs')}
              >
                <CardFront>
                  <h4 className="mb-2 flex items-center justify-center text-sm font-semibold">
                    <i className="ra ra-users mr-2 text-lg" />
                    One-Hit Or It Flees
                  </h4>
                  <p className="text-xs leading-relaxed text-gray-700 dark:text-gray-200">
                    Celestials are tougher than the locals on that plane and hit
                    much harder. If you miss the one-hit kill, the Celestial
                    flees—gear matters most, always.
                  </p>
                </CardFront>

                <CardBack
                  link_title="view"
                  on_click_link={() =>
                    console.log('view', 'One-Hit Or It Flees')
                  }
                >
                  <h4 className="mb-2 flex items-center justify-center text-sm font-semibold">
                    <i className="ra ra-users mr-2 text-lg" />
                    One-Hit Or It Flees
                  </h4>
                  <p className="text-xs leading-relaxed">
                    Celestials outscale normal monsters, especially when they
                    spawn on tougher planes like Shadow. Come levelled and fully
                    enchanted, because failing a one-hit kill makes them vanish
                    instantly mid-fight.
                  </p>
                </CardBack>
              </AnimatedCard>
            </div>

            <div className="mt-6">
              <dl className="mx-auto max-w-2xl space-y-4 text-left text-sm text-gray-700 dark:text-gray-200">
                <div>
                  <dt className="font-semibold">How do I access the event?</dt>
                  <dd className="mt-1">
                    Log in and start traveling. Celestials can spawn while you
                    move—use /PC to locate its quaternaries, then /PCT to
                    teleport to the Celestial and fight.
                  </dd>
                </div>

                <div>
                  <dt className="font-semibold">What level should I be?</dt>
                  <dd className="mt-1">
                    It depends on the plane the Celestial spawns on—Shadow Plane
                    Celestials are far stronger than Surface. Recommended: level
                    500+ with maxed crafted gear and maxed enchantments before
                    attempting these beasts.
                  </dd>
                </div>

                <div>
                  <dt className="font-semibold">What rewards do I get?</dt>
                  <dd className="mt-1">
                    The first player to kill a Celestial earns Shards, the event
                    currency. Shards are used in Alchemy, so hunting hard during
                    the 24-hour window turns into real, permanent progression.
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

export default WeeklyCelestialEvent;
