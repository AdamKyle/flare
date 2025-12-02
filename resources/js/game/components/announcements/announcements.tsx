import React from 'react';

import AnnouncementCard from './announcement-card';
import { UseManageAnnouncementDetailsVisibility } from './hooks/use-manage-announcement-details-visibility';
import AnnouncementsProps from './types/announcements-props';

import { useGameData } from 'game-data/hooks/use-game-data';

import Card from 'ui/cards/card';
import ContainerWithTitle from 'ui/container/container-with-title';

const Announcements = ({ on_close }: AnnouncementsProps) => {
  const { gameData } = useGameData();
  const { openAnnouncementDetails } = UseManageAnnouncementDetailsVisibility();

  if (!gameData || !gameData.announcements) {
    return null;
  }

  const renderAnnouncements = () => {
    return gameData.announcements.map((announcement) => {
      return (
        <AnnouncementCard
          key={announcement.id}
          announcement={announcement}
          on_click_announcement={openAnnouncementDetails}
        />
      );
    });
  };

  return (
    <ContainerWithTitle
      manageSectionVisibility={on_close}
      title={'Game Announcements'}
    >
      <Card>{renderAnnouncements()}</Card>
    </ContainerWithTitle>
  );
};

export default Announcements;
