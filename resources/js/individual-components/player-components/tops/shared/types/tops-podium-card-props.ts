import TopsDisplayField from "./tops-display-field";
import TopsLeaderboardRow from "./tops-leaderboard-row";

export default interface TopsPodiumCardProps {
    row: TopsLeaderboardRow;
    rank: number;
    placement: string;
    isFeatured: boolean;
    cardWidthClass: string;
    primaryMetric: TopsDisplayField;
    supportingMetrics: TopsDisplayField[];
}
