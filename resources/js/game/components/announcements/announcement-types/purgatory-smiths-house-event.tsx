import React from 'react';

import EventDetailLayout from '../components/event-detail-layout';
import { getEventTypeName } from '../enums/EventType';
import EventTypeProps from '../types/announcement-types/event-type-props';

const PurgatorySmithsHouseEvent = ({ announcement }: EventTypeProps) => {
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
          Purgatory Smith&apos;s House rewards Gold Dust and Shards for every
          fight, with Copper Coins joining in for characters holding the right
          quest item. Fighting deep into the location&apos;s monster list also
          opens the door to Legendary and Mythic Purgatory Chains gear, and can
          trigger a temporary one-hour event that boosts rewards further.
        </p>
      }
      cards={[
        {
          key: 'purgatory_chains_gear',
          aria_label: 'Toggle details for Purgatory Chains Gear',
          icon_class: 'ra ra-chain',
          title: 'Purgatory Chains Gear',
          front_body: (
            <>
              Fighting far enough down the location&apos;s monster list opens a
              chance at a Legendary Purgatory Chains item. Reach the final
              monster on the list for a chance at a Mythic roll.
            </>
          ),
          back_body: (
            <>
              These item chances require you to be fighting manually, without an
              active automation running, and apply while fighting at Purgatory
              Smith&apos;s House.
            </>
          ),
        },
        {
          key: 'three_currencies',
          aria_label: 'Toggle details for Currency Rewards',
          icon_class: 'ra ra-gold-bar',
          title: 'Currency Rewards',
          front_body: (
            <>
              Every fight here rewards Gold Dust and Shards. Characters holding
              the Copper Coin quest item also earn Copper Coins from the same
              fights.
            </>
          ),
          back_body: (
            <>
              Outside the temporary event, each currency rolls up to 750 per
              kill. While the temporary event is active, that per-kill roll
              increases up to 3,750.
            </>
          ),
        },
        {
          key: 'how_the_event_starts',
          aria_label: 'Toggle details for How The Event Starts',
          icon_class: 'ra ra-anvil',
          title: 'How The Event Starts',
          front_body: (
            <>
              Fighting manually—without an automation running—deep into the
              location&apos;s monster list can trigger a temporary one-hour
              event, as long as one isn&apos;t already running.
            </>
          ),
          back_body: (
            <>
              Once triggered, the event lasts one hour, sends a message to every
              player, and improves the currency and item rewards described above
              for its duration.
            </>
          ),
        },
      ]}
      faq={[
        {
          question: 'Where is this event?',
          answer: (
            <>
              Purgatory Smith&apos;s House is a location in Purgatory. Its
              currency and item rewards apply while fighting there, whether or
              not the temporary one-hour event is currently active.
            </>
          ),
        },
        {
          question: 'Do I need an item for Copper Coins?',
          answer: (
            <>
              Yes. Copper Coins only drop here for characters holding the item
              that grants that effect. Gold Dust and Shards drop for everyone
              fighting at the location.
            </>
          ),
        },
        {
          question: 'Does automation still work here?',
          answer: (
            <>
              The currency rewards apply regardless, but the Legendary and
              Mythic Purgatory Chains item chances only apply while you are
              fighting manually, without an automation running.
            </>
          ),
        },
        {
          question: 'What happens when the temporary event ends?',
          answer: (
            <>
              Currency rolls return to their normal maximum until eligible
              fighting at the location triggers the temporary event again.
            </>
          ),
        },
      ]}
    />
  );
};

export default PurgatorySmithsHouseEvent;
