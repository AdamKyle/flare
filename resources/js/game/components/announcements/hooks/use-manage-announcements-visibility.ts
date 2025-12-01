import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import { AnnouncementEvents } from '../event-types/announcement-events';
import UseManageAnnouncementsVisibilityDefinition from './definitions/use-manage-announcements-visibility-definition';

export const UseManageAnnouncementsVisibility =
  (): UseManageAnnouncementsVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const [showAnnouncements, setShowAnnouncements] = useState(false);

    const manageInventoryEmitter = eventSystem.fetchOrCreateEventEmitter<{
      [key: string]: boolean;
    }>(AnnouncementEvents.OPEN_ANNOUNCEMENTS);

    useEffect(() => {
      const updateVisibility = (visible: boolean) => {
        setShowAnnouncements(visible);
      };

      manageInventoryEmitter.on(
        AnnouncementEvents.OPEN_ANNOUNCEMENTS,
        updateVisibility
      );

      return () => {
        manageInventoryEmitter.off(
          AnnouncementEvents.OPEN_ANNOUNCEMENTS,
          updateVisibility
        );
      };
    }, [manageInventoryEmitter]);

    const openAnnouncements = () => {
      manageInventoryEmitter.emit(AnnouncementEvents.OPEN_ANNOUNCEMENTS, true);
    };

    const closeAnnouncements = () => {
      manageInventoryEmitter.emit(AnnouncementEvents.OPEN_ANNOUNCEMENTS, false);
    };

    return {
      showAnnouncements,
      openAnnouncements,
      closeAnnouncements,
    };
  };
