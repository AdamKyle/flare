import { useCallback, useState } from "react";
import {
    fetchStaleRewardQueues,
    repairStaleRewardQueues,
} from "../ajax/reward-queue-api";
import { RepairSummary, StaleQueue } from "../types/reward-queue";

export default function useStaleRewardQueues() {
    const [staleQueues, setStaleQueues] = useState<StaleQueue[]>([]);
    const [repairing, setRepairing] = useState(false);

    const refreshStaleQueues = useCallback(async () => {
        const staleData = await fetchStaleRewardQueues();
        setStaleQueues(staleData);
    }, []);

    const repair = useCallback(async (): Promise<RepairSummary> => {
        setRepairing(true);

        try {
            return await repairStaleRewardQueues();
        } finally {
            setRepairing(false);
        }
    }, []);

    return {
        staleQueues,
        repairing,
        refreshStaleQueues,
        repair,
    };
}
