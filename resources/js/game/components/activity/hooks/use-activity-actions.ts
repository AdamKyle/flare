import { useCallback } from 'react';

import UseActivityActionsDefinition from './definitions/use-activity-actions-definition';
import { UseManageAnnouncementsVisibility } from '../../announcements/hooks/use-manage-announcements-visibility';
import { useManageDonationsVisibility } from '../../donations/hooks/use-manage-donations-visibility';
import { useManageGuideQuestsVisibility } from '../../guide-quests/hooks/use-manage-guide-quests-visibility';

import { useGameData } from 'game-data/hooks/use-game-data';

export const useActivityActions = (): UseActivityActionsDefinition => {
  const { gameData, markAnnouncementsSeen } = useGameData();
  const { openAnnouncements } = UseManageAnnouncementsVisibility();
  const { openDonationScreen } = useManageDonationsVisibility();
  const { openGuideQuestsScreen } = useManageGuideQuestsVisibility();

  const handleOpenAnnouncements = useCallback(() => {
    markAnnouncementsSeen();
    openAnnouncements();
  }, [markAnnouncementsSeen, openAnnouncements]);

  return {
    has_new_announcements: gameData?.hasNewAnnouncements ?? false,
    open_announcements: handleOpenAnnouncements,
    open_guide_quests: openGuideQuestsScreen,
    open_donations: openDonationScreen,
  };
};
