import { useCallback, useState } from 'react';

import { useRewardQueueApi } from '../ajax/reward-queue-api';
import { RepairSummary, StaleQueue } from '../types/reward-queue';

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
