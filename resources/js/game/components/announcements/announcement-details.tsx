import React from 'react';

import { useFlipCard } from './hooks/use-flip-card';
import AnnouncementDetailsProps from './types/announcement-details-props';

import { useGameData } from 'game-data/hooks/use-game-data';

import AnimatedCard from 'ui/cards/animated/card-flip/animated-card';
import CardBack from 'ui/cards/animated/card-flip/card-back';
import CardFront from 'ui/cards/animated/card-flip/card-front';
import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';

const AnnouncementDetails = ({
  on_close,
  announcement_id,
}: AnnouncementDetailsProps) => {
  const { flippedCardKey, handleToggleCard } = useFlipCard();

  const { gameData } = useGameData();

  const basePath: string = import.meta.env.VITE_BASE_IMAGE_URL;
  const weeklyFactionPoints: string = `${basePath}/event-images/weekly-faction-points.png`;

  if (!gameData || !gameData.announcements) {
    return null;
  }

  const announcement = gameData.announcements.find(
    (announcement) => announcement.id === announcement_id
  );

  if (!announcement) {
    return null;
  }

  console.log(announcement);

  return (
    <ContainerWithTitle
      manageSectionVisibility={on_close}
      title="Announcement Details"
    >
      <Card>
        <div className="relative w-full overflow-hidden rounded-tl-md rounded-tr-md border-1 border-b-gray-500 dark:border-gray-700">
          <img
            src={weeklyFactionPoints}
            alt="Weekly Faction Loyalty points overview"
            className="h-40 w-full object-cover sm:h-56 md:h-64"
          />

          <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent" />

          <div className="absolute inset-0 flex items-center justify-center">
            <h4 className="rounded-md bg-black/70 px-6 py-3 text-lg font-semibold text-white shadow-lg sm:text-2xl md:text-3xl">
              Weekly Faction Loyalty Event
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
                Weekly Faction Loyalty is a limited-time event where you pledge
                to a plane and help its NPCs with Faction tasks to raise Fame.
                Higher Fame strengthens your kingdoms&apos; item defence,
                unlocks story quests, and increases your chances of earning
                powerful unique items.
              </p>
              <p className="mx-auto mt-4 max-w-2xl border-t border-gray-200 pt-2 text-xs text-gray-600 dark:border-gray-700 dark:text-gray-300">
                Click a card to flip it and learn more about each part of the
                event.
              </p>
            </div>

            <div className="mx-auto w-full max-w-5xl md:max-w-3xl">
              <div className="grid gap-4 md:grid-cols-3">
                <AnimatedCard
                  aria_label="Toggle details for Bonus Rewards"
                  is_flipped={flippedCardKey === 'bonus_rewards'}
                  on_click_card={() => handleToggleCard('bonus_rewards')}
                >
                  <CardFront>
                    <h4 className="mb-2 flex items-center justify-center text-sm font-semibold">
                      <i className="ra ra-gem mr-2 text-lg" />
                      Bonus Rewards
                    </h4>
                    <p className="text-xs leading-relaxed text-gray-700 dark:text-gray-200">
                      Turn a single day of Faction work into accelerated
                      progress. With Fame requirements temporarily halved, you
                      can push key NPCs to important thresholds faster and reach
                      the XP, gold, item, and kingdom-defence rewards tied to
                      those levels sooner.
                    </p>
                  </CardFront>

                  <CardBack
                    link_title="view"
                    on_click_link={() => console.log('view', 'Bonus Rewards')}
                  >
                    <h4 className="mb-2 flex items-center justify-center text-sm font-semibold">
                      <i className="ra ra-gem mr-2 text-lg" />
                      Bonus Rewards
                    </h4>
                    <p className="text-xs leading-relaxed">
                      Plan a focused session around your pledged plane while
                      Fame requirements are reduced. Chain key NPC task lines to
                      finish slow Fame grinds and position your kingdoms for
                      stronger item defence and earlier access to unique-item
                      rewards.
                    </p>
                  </CardBack>
                </AnimatedCard>

                <AnimatedCard
                  aria_label="Toggle details for Faction Tasks"
                  is_flipped={flippedCardKey === 'faction_tasks'}
                  on_click_card={() => handleToggleCard('faction_tasks')}
                >
                  <CardFront>
                    <h4 className="mb-2 flex items-center justify-center text-sm font-semibold">
                      <i className="ra ra-scroll-unfurled mr-2 text-lg" />
                      Faction Tasks
                    </h4>
                    <p className="text-xs leading-relaxed text-gray-700 dark:text-gray-200">
                      Faction bounty and crafting tasks are how you gain Fame
                      with NPCs on a plane. Completing their requests during the
                      event is easier, letting you climb ranks faster and keep
                      your kingdoms moving toward full protection.
                    </p>
                  </CardFront>

                  <CardBack
                    link_title="view"
                    on_click_link={() => console.log('view', 'Faction Tasks')}
                  >
                    <h4 className="mb-2 flex items-center justify-center text-sm font-semibold">
                      <i className="ra ra-scroll-unfurled mr-2 text-lg" />
                      Faction Tasks
                    </h4>
                    <p className="text-xs leading-relaxed">
                      During Weekly Faction Loyalty, all Fame requirements for
                      both bounty and crafting tasks are cut in half. This makes
                      it far less tedious to clear multiple NPCs in one session
                      and level the characters tied to important story quests.
                    </p>
                  </CardBack>
                </AnimatedCard>

                <AnimatedCard
                  aria_label="Toggle details for Pledge And Help NPCs"
                  is_flipped={flippedCardKey === 'pledge_help_npcs'}
                  on_click_card={() => handleToggleCard('pledge_help_npcs')}
                >
                  <CardFront>
                    <h4 className="mb-2 flex items-center justify-center text-sm font-semibold">
                      <i className="ra ra-users mr-2 text-lg" />
                      Pledge And Help NPCs
                    </h4>
                    <p className="text-xs leading-relaxed text-gray-700 dark:text-gray-200">
                      Use the Faction Fame system to pledge loyalty to a plane,
                      then help its NPCs with tasks to raise Fame, harden
                      kingdom item defence, and earn{' '}
                      <a
                        href="#"
                        target="_blank"
                        rel="noreferrer"
                        className="font-semibold underline"
                      >
                        unique items
                      </a>{' '}
                      more quickly.
                    </p>
                  </CardFront>

                  <CardBack
                    link_title="view"
                    on_click_link={() =>
                      console.log('view', 'Pledge And Help NPCs')
                    }
                  >
                    <h4 className="mb-2 flex items-center justify-center text-sm font-semibold">
                      <i className="ra ra-users mr-2 text-lg" />
                      Pledge And Help NPCs
                    </h4>
                    <p className="text-xs leading-relaxed">
                      Helping NPCs during this event cuts the Fame grind roughly
                      in half, letting you unlock Fame-gated quests with
                      specific NPCs and advance your chosen plane&apos;s story
                      and kingdom protection sooner.
                    </p>
                  </CardBack>
                </AnimatedCard>
              </div>

              <div className="mt-6">
                <dl className="mx-auto max-w-2xl space-y-4 text-left text-sm text-gray-700 dark:text-gray-200">
                  <div>
                    <dt className="font-semibold">
                      How do I access the event?
                    </dt>
                    <dd className="mt-1">
                      You need at least one plane&apos;s Faction at level 5 on
                      your character sheet. From there, pledge to that plane in
                      the Faction section, then use the new Faction tab on the
                      Game screen to assist NPCs with their bounty and crafting
                      tasks during the event window.
                    </dd>
                  </div>

                  <div>
                    <dt className="font-semibold">
                      Can I use automation on bounty tasks?
                    </dt>
                    <dd className="mt-1">
                      No. Bounty tasks must be completed manually, so you will
                      need to click through the tasks yourself even while the
                      Fame requirements are reduced.
                    </dd>
                  </div>

                  <div>
                    <dt className="font-semibold">What rewards do I get?</dt>
                    <dd className="mt-1">
                      You earn XP, gold, items, and kingdom item defence that
                      protects your kingdoms from players dropping items on them
                      to do damage. When combined with Faction systems, this
                      also helps you reach the Fame levels needed for powerful
                      unique items and story-driven quests tied to specific
                      NPCs.
                    </dd>
                  </div>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </Card>
    </ContainerWithTitle>
  );
};

export default AnnouncementDetails;
