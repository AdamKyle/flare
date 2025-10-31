import { useEffect, useState } from 'react';

import UseUnreadBadgesDefinition from './definitions/use-unread-badges-definition';
import UseUnreadBadgesParamsDefinition from './definitions/use-unread-badges-params-definition';

const useUnreadBadges = (
  params: UseUnreadBadgesParamsDefinition
): UseUnreadBadgesDefinition => {
  const {
    serverCount,
    announcementsCount,
    serverIndex,
    announcementsIndex,
    initialActiveIndex = 0,
  } = params;

  const [activeTabIndex, setActiveTabIndex] =
    useState<number>(initialActiveIndex);
  const [unreadServer, setUnreadServer] = useState<boolean>(false);
  const [unreadAnnouncements, setUnreadAnnouncements] =
    useState<boolean>(false);
  const [lastSeenServerCount, setLastSeenServerCount] = useState<number>(0);
  const [lastSeenAnnouncementsCount, setLastSeenAnnouncementsCount] =
    useState<number>(0);

  useEffect(() => {
    if (activeTabIndex === serverIndex) {
      setLastSeenServerCount(serverCount);

      setUnreadServer(false);
    } else {
      if (serverCount > lastSeenServerCount) {
        setUnreadServer(true);
      }
    }

    if (activeTabIndex === announcementsIndex) {
      setLastSeenAnnouncementsCount(announcementsCount);

      setUnreadAnnouncements(false);
    } else {
      if (announcementsCount > lastSeenAnnouncementsCount) {
        setUnreadAnnouncements(true);
      }
    }
  }, [
    activeTabIndex,
    serverIndex,
    announcementsIndex,
    serverCount,
    announcementsCount,
    lastSeenServerCount,
    lastSeenAnnouncementsCount,
  ]);

  const handleActiveIndexChange = (index: number): void => {
    setActiveTabIndex(index);

    if (index === serverIndex) {
      setLastSeenServerCount(serverCount);

      setUnreadServer(false);
    }

    if (index === announcementsIndex) {
      setLastSeenAnnouncementsCount(announcementsCount);

      setUnreadAnnouncements(false);
    }
  };

  return {
    unreadServer,
    unreadAnnouncements,
    activeTabIndex,
    handleActiveIndexChange,
  };
};

export default useUnreadBadges;
