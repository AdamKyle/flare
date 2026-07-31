export default interface StaleQueueAlertProps {
    count: number;
    repairing: boolean;
    onView: () => void;
    onRepair: () => void;
}
