import React from 'react';

import EventDetailLayout from '../components/event-detail-layout';
import { getEventTypeName } from '../enums/EventType';
import EventTypeProps from '../types/announcement-types/event-type-props';

const WinterEvent = ({ announcement }: EventTypeProps) => {
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
          The Ice Plane opens for the Winter Event. Fighting there earns
          progress toward a community-wide reward goal, a chance at a Christmas
          gift from Mr. Whiskers, and access to The Old Church and two Ice Plane
          Raids while the event runs.
        </p>
      }
      cards={[
        {
          key: 'ice_plane_access',
          aria_label: 'Toggle details for Ice Plane Access',
          icon_class: 'ra ra-snowflake',
          title: 'Ice Plane Access',
          front_body: (
            <>
              Traverse to the Ice Plane to take part. Monsters there hit harder
              than most other planes, so come prepared before fighting.
            </>
          ),
          back_body: (
            <>
              The Ice Plane also hosts The Old Church, which needs its own quest
              item to unlock its bonus currency and Corrupted Ice rewards.
            </>
          ),
        },
        {
          key: 'community_goal',
          aria_label: 'Toggle details for Community Reward Goal',
          icon_class: 'ra ra-crown',
          title: 'Community Reward Goal',
          front_body: (
            <>
              Every kill on the Ice Plane counts toward a shared, server-wide
              kill goal. Reaching each milestone hands out Corrupted Ice gear to
              everyone who took part.
            </>
          ),
          back_body: (
            <>
              Milestones repeat as the community keeps clearing kills, so every
              fight you land during the event helps push the next reward closer.
            </>
          ),
        },
        {
          key: 'gifts_and_raids',
          aria_label: 'Toggle details for Gifts and Raids',
          icon_class: 'ra ra-gem',
          title: 'Gifts & Raids',
          front_body: (
            <>
              Fighting on the Ice Plane gives a chance at a Christmas gift from
              Mr. Whiskers—gear that can sometimes carry a specialty item line
              like Corrupted Ice.
            </>
          ),
          back_body: (
            <>
              The Winter Event also opens the door to the Ice Queen and Frozen
              King Raids, each with their own quests and rewards.
            </>
          ),
        },
      ]}
      faq={[
        {
          question: 'How do I access the event?',
          answer: (
            <>
              Traverse to the Ice Plane from the map. Once there, fighting
              counts toward both your own rewards and the community reward goal
              automatically.
            </>
          ),
        },
        {
          question: 'What is Mr. Whiskers?',
          answer: (
            <>
              Mr. Whiskers is the Winter Event&apos;s gift-giver—fighting on the
              Ice Plane gives a chance at a gift, which can occasionally carry a
              specialty gear line such as Corrupted Ice.
            </>
          ),
        },
        {
          question: 'What happens when it ends?',
          answer: (
            <>
              The Ice Plane closes back down and Ice Plane fighting no longer
              counts toward the community goal or Christmas gifts until the
              event returns.
            </>
          ),
        },
      ]}
    />
  );
};

export default WinterEvent;
