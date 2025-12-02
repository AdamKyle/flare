import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import { AnnouncementEvents } from '../event-types/announcement-events';
import UseManageAnnouncementsVisibilityDefinition from './definitions/use-manage-announcements-visibility-definition';

export const UseManageAnnouncementsVisibility =
  (): UseManageAnnouncementsVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const [showAnnouncements, setShowAnnouncements] = useState(false);

    const manageAnnouncementVisibility = eventSystem.fetchOrCreateEventEmitter<{
      [key: string]: boolean;
    }>(AnnouncementEvents.OPEN_ANNOUNCEMENTS);

    useEffect(() => {
      const updateVisibility = (visible: boolean) => {
        setShowAnnouncements(visible);
      };

      manageAnnouncementVisibility.on(
        AnnouncementEvents.OPEN_ANNOUNCEMENTS,
        updateVisibility
      );

      return () => {
        manageAnnouncementVisibility.off(
          AnnouncementEvents.OPEN_ANNOUNCEMENTS,
          updateVisibility
        );
      };
    }, [manageAnnouncementVisibility]);

    const openAnnouncements = () => {
      manageAnnouncementVisibility.emit(
        AnnouncementEvents.OPEN_ANNOUNCEMENTS,
        true
      );
    };

    const closeAnnouncements = () => {
      manageAnnouncementVisibility.emit(
        AnnouncementEvents.OPEN_ANNOUNCEMENTS,
        false
      );
    };

    return {
      showAnnouncements,
      openAnnouncements,
      closeAnnouncements,
    };
  };
