import React from 'react';

import EventDetailLayout from '../components/event-detail-layout';
import { getEventTypeName } from '../enums/EventType';
import EventTypeProps from '../types/announcement-types/event-type-props';

const DelusionalMemoriesEvent = ({ announcement }: EventTypeProps) => {
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
          The Delusional Memories plane opens, tougher than its neighboring
          Twisted Memories plane. The event moves through stages—fighting, then
          crafting, then enchanting—each with its own shared community goal,
          alongside access to the Jester of Time and Corrupted Bishop Raids.
        </p>
      }
      cards={[
        {
          key: 'plane_access',
          aria_label: 'Toggle details for Plane Access',
          icon_class: 'ra ra-book',
          title: 'Plane Access',
          front_body: (
            <>
              Traverse to the Delusional Memories plane to take part. Monsters
              here are noticeably stronger than on Twisted Memories, so come
              geared up.
            </>
          ),
          back_body: (
            <>
              The plane also carries its own story and quests, unlocked
              alongside the event for players who reach it.
            </>
          ),
        },
        {
          key: 'community_goal',
          aria_label: 'Toggle details for Community Reward Goal',
          icon_class: 'ra ra-gem-pendant',
          title: 'Community Reward Goal',
          front_body: (
            <>
              The event runs through stages: a fighting stage on the plane
              first, then a crafting stage, then an enchanting stage—each stage
              tracks its own shared, server-wide progress goal.
            </>
          ),
          back_body: (
            <>
              Reaching a stage&apos;s milestone hands out Delusional Silver gear
              to everyone who contributed to it. Only the currently active stage
              counts, so check which one is running before you dive in.
            </>
          ),
        },
        {
          key: 'raids',
          aria_label: 'Toggle details for Raids',
          icon_class: 'ra ra-hourglass',
          title: 'Jester of Time & Corrupted Bishop',
          front_body: (
            <>
              The event opens the door to two Raids tied to this plane: the
              Jester of Time Raid and the Corrupted Bishop Raid.
            </>
          ),
          back_body: (
            <>
              Both Raids have their own quests, access requirements, and
              rewards—check each Raid&apos;s own announcement for details while
              it is live.
            </>
          ),
        },
      ]}
      faq={[
        {
          question: 'How do I access the event?',
          answer: (
            <>
              Traverse to the Delusional Memories plane. Whichever stage is
              currently active—fighting, crafting, or enchanting—decides what
              counts toward the shared reward goal at that moment.
            </>
          ),
        },
        {
          question: 'What is Delusional Silver?',
          answer: (
            <>
              Delusional Silver is the specialty gear line handed out as the
              community reward goal is reached during this event.
            </>
          ),
        },
        {
          question: 'What happens when it ends?',
          answer: (
            <>
              The plane closes back down and progress toward the community goal
              stops accumulating until the event returns.
            </>
          ),
        },
      ]}
    />
  );
};

export default DelusionalMemoriesEvent;
