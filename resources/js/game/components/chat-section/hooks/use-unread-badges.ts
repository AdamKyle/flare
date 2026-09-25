import { useEffect, useState } from 'react';

import UseUnreadBadgesDefinition from './definitions/use-unread-badges-definition';
import UseUnreadBadgesParamsDefinition from './definitions/use-unread-badges-params-definition';

const useUnreadBadges = ({
  serverCount,
  serverIndex,
  explorationCount,
  explorationIndex,
  initialActiveIndex = 0,
}: UseUnreadBadgesParamsDefinition): UseUnreadBadgesDefinition => {
  const [activeTabIndex, setActiveTabIndex] =
    useState<number>(initialActiveIndex);
  const [unreadServer, setUnreadServer] = useState<boolean>(false);
  const [lastSeenServerCount, setLastSeenServerCount] = useState<number>(0);
  const [unreadExploration, setUnreadExploration] = useState<boolean>(false);
  const [lastSeenExplorationCount, setLastSeenExplorationCount] =
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
  }, [activeTabIndex, serverIndex, serverCount, lastSeenServerCount]);

  useEffect(() => {
    if (activeTabIndex === explorationIndex) {
      setLastSeenExplorationCount(explorationCount);

      setUnreadExploration(false);
    } else {
      if (explorationCount > lastSeenExplorationCount) {
        setUnreadExploration(true);
      }
    }
  }, [
    activeTabIndex,
    explorationIndex,
    explorationCount,
    lastSeenExplorationCount,
  ]);

  const handleActiveIndexChange = (index: number): void => {
    setActiveTabIndex(index);

    if (index === serverIndex) {
      setLastSeenServerCount(serverCount);

      setUnreadServer(false);
    }

    if (index === explorationIndex) {
      setLastSeenExplorationCount(explorationCount);

      setUnreadExploration(false);
    }
  };

  return {
    unreadServer,
    unreadExploration,
    activeTabIndex,
    handleActiveIndexChange,
  };
};

export default useUnreadBadges;
