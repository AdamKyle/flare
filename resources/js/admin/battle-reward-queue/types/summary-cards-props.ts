import { Summary } from '../api/definitions/reward-queue-definition';

export default interface SummaryCardsProps {
  summary: Summary;
  onFilter?: (status: string) => void;
}
