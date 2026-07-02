import TopsDisplayField from "./tops-display-field";
import TopsLeaderboardRow from "./tops-leaderboard-row";

export default interface TopsPodiumProps {
    rows: TopsLeaderboardRow[];
    primaryMetric: TopsDisplayField;
    supportingMetrics: TopsDisplayField[];
}
