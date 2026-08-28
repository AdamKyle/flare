import { useCallback, useState } from 'react';

import {
  RepairSummary,
  StaleQueue,
} from '../api/definitions/reward-queue-definition';
import { useRewardQueueApi } from '../api/hooks/use-reward-queue-api';

export default function useStaleRewardQueues() {
  const { fetchStaleRewardQueues, repairStaleRewardQueues } =
    useRewardQueueApi();

  const [staleQueues, setStaleQueues] = useState<StaleQueue[]>([]);
  const [repairing, setRepairing] = useState(false);

  const refreshStaleQueues = useCallback(async () => {
    const staleData = await fetchStaleRewardQueues();
    setStaleQueues(staleData);
  }, [fetchStaleRewardQueues]);

  const repair = useCallback(async (): Promise<RepairSummary> => {
    setRepairing(true);

    try {
      return await repairStaleRewardQueues();
    } finally {
      setRepairing(false);
    }
  }, [repairStaleRewardQueues]);

  return {
    staleQueues,
    repairing,
    refreshStaleQueues,
    repair,
  };
}
