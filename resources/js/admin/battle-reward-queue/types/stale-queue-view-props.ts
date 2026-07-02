import { StaleQueue } from "./reward-queue";

export default interface StaleQueueViewProps {
    staleQueues: StaleQueue[];
    repairing: boolean;
    onBack: () => void;
    onRepair: () => void;
}
