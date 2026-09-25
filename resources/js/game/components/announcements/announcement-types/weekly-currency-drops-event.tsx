import React from 'react';

import EventDetailLayout from '../components/event-detail-layout';
import { getEventTypeName } from '../enums/EventType';
import EventTypeProps from '../types/announcement-types/event-type-props';

const WeeklyCurrencyDropsEvent = ({ announcement }: EventTypeProps) => {
  const basePath: string = import.meta.env.VITE_BASE_IMAGE_URL;
  const heroImage: string = `${basePath}/event-images/weekly-faction-points.png`;

  return (
    <EventDetailLayout
      hero_image_src={heroImage}
      hero_alt=""
      title={getEventTypeName(announcement.event.type)}
      ends_at_formatted={announcement.expires_at_formatted}
      intro={
        <p>
          While this event runs, every kill (celestial kills excepted) grants
          bonus Shards and Gold Dust on top of your normal rewards. No setup is
          needed—just keep fighting where you normally would.
        </p>
      }
      cards={[
        {
          key: 'bonus_shards_dust',
          aria_label: 'Toggle details for Bonus Shards and Gold Dust',
          icon_class: 'ra ra-gem',
          title: 'Bonus Shards & Gold Dust',
          front_body: (
            <>
              Every qualifying kill grants extra Shards and Gold Dust on top of
              your usual drops, scaled by how many monsters you clear.
            </>
          ),
          back_body: (
            <>
              Both currencies feed Alchemy, and the bonus applies to every
              qualifying kill for the full duration of the event.
            </>
          ),
        },
        {
          key: 'copper_coins',
          aria_label: 'Toggle details for Copper Coins',
          icon_class: 'ra ra-gold-bar',
          title: 'Copper Coins',
          front_body: (
            <>
              Characters carrying the right quest item also earn bonus Copper
              Coins per kill while the event is active.
            </>
          ),
          back_body: (
            <>
              Copper Coins power systems like Reincarnation, gems, and trinkets,
              so this is a good window to farm them if you already hold the item
              that unlocks the bonus.
            </>
          ),
        },
        {
          key: 'ordinary_gameplay',
          aria_label: 'Toggle details for Ordinary Gameplay',
          icon_class: 'ra ra-sword',
          title: 'Just Keep Fighting',
          front_body: (
            <>
              There is no special location or setup required—manual fighting and
              Exploration both count toward the bonus while the event is live.
            </>
          ),
          back_body: (
            <>
              Any qualifying kill anywhere you can normally fight counts, so
              this event rewards however you already play.
            </>
          ),
        },
      ]}
      faq={[
        {
          question: 'How do I take part?',
          answer: (
            <>
              Just fight as you normally would. The currency bonus applies
              automatically to qualifying kills while the event is running.
            </>
          ),
        },
        {
          question: 'Does this apply to Celestials?',
          answer: (
            <>
              No. Celestial kills are excluded from the Weekly Currency Drops
              bonus.
            </>
          ),
        },
        {
          question: 'What happens when it ends?',
          answer: (
            <>
              Currency drops return to normal and the event announcement is
              removed. It repeats on its usual weekly schedule.
            </>
          ),
        },
      ]}
    />
  );
};

export default WeeklyCurrencyDropsEvent;
