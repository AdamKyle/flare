import React from 'react';

import CurrencyDisplay from '../../../reusable-components/currency/currency-display';
import { CurrencyDisplayMode } from '../../../reusable-components/currency/enums/currency-display-mode';
import { CurrencyType } from '../../../reusable-components/currency/enums/currency-type';
import EventDetailLayout from '../components/event-detail-layout';
import { getEventTypeName } from '../enums/EventType';
import EventTypeProps from '../types/announcement-types/event-type-props';

const renderExactAmount = (currency: CurrencyType, amount: number) => (
  <CurrencyDisplay
    currency={currency}
    amount={amount}
    display_mode={CurrencyDisplayMode.EXACT}
  />
);

const GoldMinesEvent = ({ announcement }: EventTypeProps) => {
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
          Something has flooded the Gold Mines with treasure. For the next hour,
          fighting at the Gold Mines location pays out far more currency than
          usual, with a real chance at unique gear from deeper in the shafts.
        </p>
      }
      cards={[
        {
          key: 'boosted_currency',
          aria_label: 'Toggle details for Boosted Currency',
          icon_class: 'ra ra-mine-wagon',
          title: 'Boosted Currency',
          front_body: (
            <>
              Kills at the Gold Mines location always pay Gold, Gold Dust, and
              Shards. While this surge event is active, those payouts are
              significantly higher than normal.
            </>
          ),
          back_body: (
            <>
              Normally Gold Mines pays up to{' '}
              {renderExactAmount(CurrencyType.GOLD, 750)} and up to{' '}
              {renderExactAmount(CurrencyType.GOLD_DUST, 375)} and{' '}
              {renderExactAmount(CurrencyType.SHARDS, 375)} per kill. During the
              surge, those caps rise to{' '}
              {renderExactAmount(CurrencyType.GOLD, 3750)} and{' '}
              {renderExactAmount(CurrencyType.GOLD_DUST, 750)} and{' '}
              {renderExactAmount(CurrencyType.SHARDS, 750)}.
            </>
          ),
        },
        {
          key: 'unique_gear',
          aria_label: 'Toggle details for Unique Gear Chance',
          icon_class: 'ra ra-gem-pendant',
          title: 'Unique Gear Chance',
          front_body: (
            <>
              Clearing tougher monsters further down the Gold Mines monster list
              gives a chance at a randomly enchanted unique item.
            </>
          ),
          back_body: (
            <>
              Item odds and the chance itself both improve while the surge is
              active, so it pays to push toward the back half of the monster
              list during the event.
            </>
          ),
        },
        {
          key: 'random_trigger',
          aria_label: 'Toggle details for How It Starts',
          icon_class: 'ra ra-hourglass',
          title: 'How It Starts',
          front_body: (
            <>
              There is no set schedule—fighting deep into the Gold Mines monster
              list without an automation running can trigger it randomly, with a
              global message letting everyone know.
            </>
          ),
          back_body: (
            <>
              Once triggered, the surge lasts one hour and only one can be
              active at a time, so it is worth heading to the Gold Mines when
              you see the announcement.
            </>
          ),
        },
      ]}
      faq={[
        {
          question: 'Where do I go?',
          answer: (
            <>
              Head to the Gold Mines location and fight as normal—both the
              baseline currency bonus and this surge apply automatically while
              you are there.
            </>
          ),
        },
        {
          question: 'Do I need automation off?',
          answer: (
            <>
              Yes. The chance at unique gear only rolls while you have no
              automation running, so fight manually if you want a shot at an
              item drop.
            </>
          ),
        },
        {
          question: 'How long does it last?',
          answer: <>The surge lasts one hour once it triggers.</>,
        },
      ]}
    />
  );
};

export default GoldMinesEvent;
