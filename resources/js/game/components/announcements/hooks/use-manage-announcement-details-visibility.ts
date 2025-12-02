import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import { AnnouncementEvents } from '../event-types/announcement-events';
import UseManageAnnouncementDetailsVisibilityDefinition from './definitions/use-manage-announcement-details-visibility-definition';

export const UseManageAnnouncementDetailsVisibility =
  (): UseManageAnnouncementDetailsVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const [announcementId, setAnnouncementId] = useState<number | null>(null);

    const manageAnnouncementDetailVisibility =
      eventSystem.fetchOrCreateEventEmitter<{
        [key: string]: number | null;
      }>(AnnouncementEvents.VIEW_ANNOUNCEMENT);

    useEffect(() => {
      const updateVisibility = (announcementId: number | null) => {
        setAnnouncementId(announcementId);
      };

      manageAnnouncementDetailVisibility.on(
        AnnouncementEvents.VIEW_ANNOUNCEMENT,
        updateVisibility
      );

      return () => {
        manageAnnouncementDetailVisibility.off(
          AnnouncementEvents.VIEW_ANNOUNCEMENT,
          updateVisibility
        );
      };
    }, [manageAnnouncementDetailVisibility]);

    const openAnnouncementDetails = (announcementId: number | null) => {
      manageAnnouncementDetailVisibility.emit(
        AnnouncementEvents.VIEW_ANNOUNCEMENT,
        announcementId
      );
    };

    const closeAnnouncementDetails = () => {
      manageAnnouncementDetailVisibility.emit(
        AnnouncementEvents.VIEW_ANNOUNCEMENT,
        null
      );
    };

    return {
      announcementId,
      openAnnouncementDetails,
      closeAnnouncementDetails,
    };
  };
