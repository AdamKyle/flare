import { isNil } from 'lodash';
import React from 'react';
import { match } from 'ts-pattern';

import DelusionalMemoriesEvent from './announcement-types/delusional-memories-event';
import GoldMinesEvent from './announcement-types/gold-mines-event';
import PurgatorySmithsHouseEvent from './announcement-types/purgatory-smiths-house-event';
import RaidEvent from './announcement-types/raid-event';
import TheOldChurchEvent from './announcement-types/the-old-church-event';
import WeeklyCelestialEvent from './announcement-types/weekly-celestial-event';
import WeeklyCurrencyDropsEvent from './announcement-types/weekly-currency-drops-event';
import WeeklyFactionPointsEvent from './announcement-types/weekly-faction-points-event';
import WinterEvent from './announcement-types/winter-event';
import { EventType } from './enums/EventType';
import AnnouncementDetailsProps from './types/announcement-details-props';

import { useGameData } from 'game-data/hooks/use-game-data';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import ContainerWithTitle from 'ui/container/container-with-title';

const AnnouncementDetails = ({
  on_close,
  announcement_id,
}: AnnouncementDetailsProps) => {
  const { gameData } = useGameData();

  if (!gameData || !gameData.announcements) {
    return null;
  }

  const announcement = gameData.announcements.find(
    (announcement) => announcement.id === announcement_id
  );

  if (!announcement) {
    return null;
  }

  const { event } = announcement;

  // Deleting an Event nulls its Announcement's event_id rather than removing
  // the row, so an Announcement can outlive the Event it describes.
  if (isNil(event)) {
    return (
      <ContainerWithTitle
        manageSectionVisibility={on_close}
        title="Announcement Details"
      >
        <Alert variant={AlertVariant.INFO}>
          This event has ended, so there are no details left to show.
        </Alert>
      </ContainerWithTitle>
    );
  }

  const announcementWithEvent = { ...announcement, event };

  const renderAnnouncement = () => {
    return match(event.type)
      .with(EventType.WEEKLY_FACTION_LOYALTY_EVENT, () => (
        <WeeklyFactionPointsEvent announcement={announcementWithEvent} />
      ))
      .with(EventType.WEEKLY_CELESTIALS, () => (
        <WeeklyCelestialEvent announcement={announcementWithEvent} />
      ))
      .with(EventType.PURGATORY_SMITH_HOUSE, () => (
        <PurgatorySmithsHouseEvent announcement={announcementWithEvent} />
      ))
      .with(EventType.WEEKLY_CURRENCY_DROPS, () => (
        <WeeklyCurrencyDropsEvent announcement={announcementWithEvent} />
      ))
      .with(EventType.WINTER_EVENT, () => (
        <WinterEvent announcement={announcementWithEvent} />
      ))
      .with(EventType.GOLD_MINES, () => (
        <GoldMinesEvent announcement={announcementWithEvent} />
      ))
      .with(EventType.THE_OLD_CHURCH, () => (
        <TheOldChurchEvent announcement={announcementWithEvent} />
      ))
      .with(EventType.DELUSIONAL_MEMORIES_EVENT, () => (
        <DelusionalMemoriesEvent announcement={announcementWithEvent} />
      ))
      .with(EventType.RAID_EVENT, () => (
        <RaidEvent announcement={announcementWithEvent} />
      ))
      .otherwise(() => (
        <Alert variant={AlertVariant.INFO}>
          Details for this announcement are not available yet.
        </Alert>
      ));
  };

  return (
    <ContainerWithTitle
      manageSectionVisibility={on_close}
      title="Announcement Details"
    >
      {renderAnnouncement()}
    </ContainerWithTitle>
  );
};

export default AnnouncementDetails;
