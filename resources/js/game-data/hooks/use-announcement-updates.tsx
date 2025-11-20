import React, { useCallback, useState } from 'react';

import { AnnouncementUpdatesWire } from 'game-data/components/announcement-updates-wire';
import UseAnnouncementsUpdateDefinition from 'game-data/hooks/definitions/use-announcements-update-definition';
import UseAnnouncementsUpdateParamsDefinition from 'game-data/hooks/definitions/use-announcements-update-params-definition';

export const useAnnouncementUpdates = ({
  onEvent,
}: UseAnnouncementsUpdateParamsDefinition): UseAnnouncementsUpdateDefinition => {
  const [listening, setListening] = useState<boolean>(false);

  const start = useCallback(() => {
    setListening(true);
  }, []);

  const renderWire = () => {
    if (!listening) {
      return null;
    }

    return <AnnouncementUpdatesWire onEvent={onEvent} />;
  };

  return { listening, start, renderWire };
};
