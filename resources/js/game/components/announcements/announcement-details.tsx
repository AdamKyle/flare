import React from 'react';
import { match } from 'ts-pattern';

import PurgatorySmithsHouseEvent from './announcement-types/purgatory-smiths-house-event';
import WeeklyCelestialEvent from './announcement-types/weekly-celestial-event';
import WeeklyFactionPointsEvent from './announcement-types/weekly-faction-points-event';
import { EventType } from './enums/EventType';
import AnnouncementDetailsProps from './types/announcement-details-props';

import { useGameData } from 'game-data/hooks/use-game-data';

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

  const renderAnnouncement = () => {
    return match(announcement.event.type)
      .with(EventType.WEEKLY_FACTION_LOYALTY_EVENT, () => (
        <WeeklyFactionPointsEvent announcement={announcement} />
      ))
      .with(EventType.WEEKLY_CELESTIALS, () => (
        <WeeklyCelestialEvent announcement={announcement} />
      ))
      .with(EventType.PURGATORY_SMITH_HOUSE, () => (
        <PurgatorySmithsHouseEvent announcement={announcement} />
      ))
      .otherwise(() => null);
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
