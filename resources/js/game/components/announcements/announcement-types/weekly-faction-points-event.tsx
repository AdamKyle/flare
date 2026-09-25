import React from 'react';

import EventDetailLayout from '../components/event-detail-layout';
import { getEventTypeName } from '../enums/EventType';
import EventTypeProps from '../types/announcement-types/event-type-props';

const WeeklyFactionPointsEvent = ({ announcement }: EventTypeProps) => {
  const basePath: string = import.meta.env.VITE_BASE_IMAGE_URL;
  const weeklyFactionPoints: string = `${basePath}/event-images/weekly-faction-points.png`;

  return (
    <EventDetailLayout
      hero_image_src={weeklyFactionPoints}
      hero_alt=""
      title={getEventTypeName(announcement.event.type)}
      ends_at_formatted={announcement.expires_at_formatted}
      intro={
        <p>
          Weekly Faction Loyalty is a limited-time event where you pledge to a
          plane and help its NPCs with Faction tasks to raise Fame. Higher Fame
          strengthens your kingdoms&apos; item defence, unlocks story quests,
          and increases your chances of earning powerful unique items.
        </p>
      }
      cards={[
        {
          key: 'bonus_rewards',
          aria_label: 'Toggle details for Bonus Rewards',
          icon_class: 'ra ra-gem',
          title: 'Bonus Rewards',
          front_body: (
            <>
              Turn a single day of Faction work into accelerated progress. With
              Fame requirements temporarily halved, you can push key NPCs to
              important thresholds faster and reach the XP, gold, item, and
              kingdom-defence rewards tied to those levels sooner.
            </>
          ),
          back_body: (
            <>
              Plan a focused session around your pledged plane while Fame
              requirements are reduced. Chain key NPC task lines to finish slow
              Fame grinds and position your kingdoms for stronger item defence
              and earlier access to unique-item rewards.
            </>
          ),
        },
        {
          key: 'faction_tasks',
          aria_label: 'Toggle details for Faction Tasks',
          icon_class: 'ra ra-scroll-unfurled',
          title: 'Faction Tasks',
          front_body: (
            <>
              Faction bounty and crafting tasks are how you gain Fame with NPCs
              on a plane. Completing their requests during the event is easier,
              letting you climb ranks faster and keep your kingdoms moving
              toward full protection.
            </>
          ),
          back_body: (
            <>
              During Weekly Faction Loyalty, all Fame requirements for both
              bounty and crafting tasks are cut in half. This makes it far less
              tedious to clear multiple NPCs in one session and level the
              characters tied to important story quests.
            </>
          ),
        },
        {
          key: 'pledge_help_npcs',
          aria_label: 'Toggle details for Pledge And Help NPCs',
          icon_class: 'ra ra-knight-helmet',
          title: 'Pledge And Help NPCs',
          front_body: (
            <>
              Use the Faction Fame system to pledge loyalty to a plane, then
              help its NPCs with tasks to raise Fame, harden kingdom item
              defence, and earn <strong>unique items</strong> more quickly.
            </>
          ),
          back_body: (
            <>
              Helping NPCs during this event cuts the Fame grind roughly in
              half, letting you unlock Fame-gated quests with specific NPCs and
              advance your chosen plane&apos;s story and kingdom protection
              sooner.
            </>
          ),
        },
      ]}
      faq={[
        {
          question: 'How do I access the event?',
          answer: (
            <>
              You need at least one plane&apos;s Faction at level 5 on your
              character sheet. From there, pledge to that plane in the Faction
              section, then use the Faction tab on the Game screen to assist
              NPCs with their bounty and crafting tasks during the event window.
            </>
          ),
        },
        {
          question: 'Can I use automation on bounty tasks?',
          answer: (
            <>
              Yes. Bounty task progress counts any kill of the matching monster,
              whether you fight it manually or through Exploration.
            </>
          ),
        },
        {
          question: 'What rewards do I get?',
          answer: (
            <>
              You earn XP, gold, items, and kingdom item defence that protects
              your kingdoms from players dropping items on them to do damage.
              When combined with Faction systems, this also helps you reach the
              Fame levels needed for powerful unique items and story-driven
              quests tied to specific NPCs.
            </>
          ),
        },
      ]}
    />
  );
};

export default WeeklyFactionPointsEvent;
