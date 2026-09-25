import React from 'react';

import EventDetailLayout from '../components/event-detail-layout';
import { getEventTypeName } from '../enums/EventType';
import EventTypeProps from '../types/announcement-types/event-type-props';

const WeeklyCelestialEvent = ({ announcement }: EventTypeProps) => {
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
          For the next 24 hours, the gates are cracked open and Celestials can
          spill onto the planes. Any travel—directional movement, teleporting,
          setting sail, or traversing—can trigger a spawn (at a high rate), so
          keep moving, find the target with /PC, then use /PCT to jump in and
          claim the kill for Shards you can spend in Alchemy.
        </p>
      }
      cards={[
        {
          key: 'bonus_rewards',
          aria_label: 'Toggle details for Spawn By Moving',
          icon_class: 'ra ra-gem',
          title: 'Spawn By Moving',
          front_body: (
            <>
              Every step, teleport, sail, or traverse can spark a Celestial
              spawn somewhere in the planes. Keep moving, then use /PCT to drop
              straight into the fight.
            </>
          ),
          back_body: (
            <>
              Travel in any way—directional moves, teleports, sailing, or
              traversing—and spawns roll at a high rate. When one appears, use
              /PCT to teleport to it and strike.
            </>
          ),
        },
        {
          key: 'faction_tasks',
          aria_label: 'Toggle details for Earn Shards',
          icon_class: 'ra ra-scroll-unfurled',
          title: 'Earn Shards',
          front_body: (
            <>
              Be first to kill a Celestial and you earn Shards, a rare event
              currency. Spend them in Alchemy to convert hunts into lasting,
              truly godly power.
            </>
          ),
          back_body: (
            <>
              Shards go to whoever lands the first kill, so speed and scouting
              win. Stockpile Shards and use Alchemy to craft, upgrade, and push
              your build forward even faster.
            </>
          ),
        },
        {
          key: 'pledge_help_npcs',
          aria_label: 'Toggle details for One-Hit Or It Flees',
          icon_class: 'ra ra-on-target',
          title: 'One-Hit Or It Flees',
          front_body: (
            <>
              Celestials are tougher than the locals on that plane and hit much
              harder. If you miss the one-hit kill, the Celestial flees—gear
              matters most, always.
            </>
          ),
          back_body: (
            <>
              Celestials outscale normal monsters, especially when they spawn on
              tougher planes like Shadow. Come levelled and fully enchanted,
              because failing a one-hit kill makes them vanish instantly
              mid-fight.
            </>
          ),
        },
      ]}
      faq={[
        {
          question: 'How do I access the event?',
          answer: (
            <>
              Log in and start traveling. Celestials can spawn while you
              move—use /PC to locate its quaternaries, then /PCT to teleport to
              the Celestial and fight.
            </>
          ),
        },
        {
          question: 'What level should I be?',
          answer: (
            <>
              It depends on the plane the Celestial spawns on—Shadow Plane
              Celestials are far stronger than Surface. Recommended: level 500+
              with maxed crafted gear and maxed enchantments before attempting
              these beasts.
            </>
          ),
        },
        {
          question: 'What rewards do I get?',
          answer: (
            <>
              The first player to kill a Celestial earns Shards, the event
              currency. Shards are used in Alchemy, so hunting hard during the
              24-hour window turns into real, permanent progression.
            </>
          ),
        },
      ]}
    />
  );
};

export default WeeklyCelestialEvent;
