import React from 'react';

import EventDetailLayout from '../components/event-detail-layout';
import { isRaidType } from '../enums/RaidType';
import { resolveRaidLore } from './raid-content/raid-lore';
import EventTypeProps from '../types/announcement-types/event-type-props';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';

const RaidEvent = ({ announcement }: EventTypeProps) => {
  const basePath: string = import.meta.env.VITE_BASE_IMAGE_URL;
  const heroImage: string = `${basePath}/event-images/weekly-faction-points.png`;

  const raidIdentity = announcement.event.raid_identity;

  if (!raidIdentity || !isRaidType(raidIdentity.raid_type)) {
    return (
      <Alert variant={AlertVariant.INFO}>
        A Raid is currently running. Check the in-game chat announcements for
        this Raid&apos;s specific details.
      </Alert>
    );
  }

  const lore = resolveRaidLore(raidIdentity.raid_type);

  return (
    <EventDetailLayout
      hero_image_src={heroImage}
      hero_alt=""
      title={raidIdentity.name}
      ends_at_formatted={announcement.expires_at_formatted}
      intro={
        <>
          <p className="font-semibold">{lore.tagline}</p>
          <p className="mt-2">{lore.story}</p>
        </>
      }
      cards={[lore.cards[0], lore.cards[1], lore.cards[2]]}
      faq={[
        lore.faq[0],
        lore.faq[1],
        {
          question: 'How many boss attempts do I get?',
          answer: (
            <>
              Five attempts per character per day against the Raid boss,
              resetting once daily while the Raid remains active.
            </>
          ),
        },
        {
          question: 'Is there a cost to attempt the Raid boss?',
          answer: <>No. Attempting the Raid boss is free.</>,
        },
      ]}
    />
  );
};

export default RaidEvent;
