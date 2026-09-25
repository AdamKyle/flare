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

const TheOldChurchEvent = ({ announcement }: EventTypeProps) => {
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
          The Emerald Prince has appeared at The Old Church, on the Ice Plane.
          For the next hour, fighting there with the right quest item pays far
          more currency than usual, with a chance at Corrupted Ice gear.
        </p>
      }
      cards={[
        {
          key: 'requires_quest_item',
          aria_label: 'Toggle details for Access Requirement',
          icon_class: 'ra ra-key',
          title: 'Access Requirement',
          front_body: (
            <>
              The Old Church only pays out to characters carrying its specific
              quest item—simply being at the location is not enough.
            </>
          ),
          back_body: (
            <>
              Without the quest item, fights at The Old Church behave like any
              other fight on the Ice Plane, with none of the bonus rewards
              below.
            </>
          ),
        },
        {
          key: 'high_currency',
          aria_label: 'Toggle details for High-Value Currency',
          icon_class: 'ra ra-mine-wagon',
          title: 'High-Value Currency',
          front_body: (
            <>
              Normally The Old Church pays up to{' '}
              {renderExactAmount(CurrencyType.GOLD, 15000)} and up to{' '}
              {renderExactAmount(CurrencyType.GOLD_DUST, 750)} and{' '}
              {renderExactAmount(CurrencyType.SHARDS, 750)} per kill. This surge
              doubles those caps.
            </>
          ),
          back_body: (
            <>
              While the surge is active, per-kill caps rise to{' '}
              {renderExactAmount(CurrencyType.GOLD, 30000)} and{' '}
              {renderExactAmount(CurrencyType.GOLD_DUST, 3750)} and{' '}
              {renderExactAmount(CurrencyType.SHARDS, 3750)}.
            </>
          ),
        },
        {
          key: 'corrupted_ice',
          aria_label: 'Toggle details for Corrupted Ice Gear',
          icon_class: 'ra ra-ice-cube',
          title: 'Corrupted Ice Gear',
          front_body: (
            <>
              Clearing tougher monsters here gives a chance at randomly
              enchanted Corrupted Ice gear, the specialty item line tied to the
              Winter Event.
            </>
          ),
          back_body: (
            <>
              Item odds improve further while the surge is active, so it is
              worth fighting through the back half of the monster list once it
              triggers.
            </>
          ),
        },
      ]}
      faq={[
        {
          question: 'How do I access The Old Church?',
          answer: (
            <>
              Traverse to the Ice Plane, then head to The Old Church location
              carrying its quest item before you fight.
            </>
          ),
        },
        {
          question: 'Is this tied to the Winter Event?',
          answer: (
            <>
              Yes. The Old Church and its Corrupted Ice rewards are part of the
              Winter Event on the Ice Plane.
            </>
          ),
        },
        {
          question: 'Does automation still work here?',
          answer: (
            <>
              The currency bonus applies regardless, but the Corrupted Ice item
              chance—and triggering the surge itself—only happen while you are
              fighting manually, without an automation running.
            </>
          ),
        },
        {
          question: 'How long does the surge last?',
          answer: <>One hour once it triggers.</>,
        },
      ]}
    />
  );
};

export default TheOldChurchEvent;
