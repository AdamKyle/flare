import { Summary } from "./reward-queue";

export default interface SummaryCardsProps {
    summary: Summary;
    onFilter?: (status: string) => void;
}
