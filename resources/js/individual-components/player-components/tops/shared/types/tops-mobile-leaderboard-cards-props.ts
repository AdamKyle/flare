import TopsDisplayField from "./tops-display-field";
import TopsLeaderboardRow from "./tops-leaderboard-row";

export default interface TopsMobileLeaderboardCardsProps {
    rows: TopsLeaderboardRow[];
    fields: TopsDisplayField[];
    primaryMetric: TopsDisplayField;
    supportingMetrics: TopsDisplayField[];
}
