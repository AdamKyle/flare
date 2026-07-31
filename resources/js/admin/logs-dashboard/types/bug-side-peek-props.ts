import { SystemBugReport } from "./logs-dashboard";

export default interface BugSidePeekProps {
    bug: SystemBugReport;
    onClose: () => void;
}
