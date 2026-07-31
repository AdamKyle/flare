import { LogEntry } from "./logs-dashboard";

export default interface LogSidePeekProps {
    entry: LogEntry;
    onClose: () => void;
}
