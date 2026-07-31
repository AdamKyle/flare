import TopsLeaderboardRow from "./tops-leaderboard-row";
import TopsPeriod from "./tops-period";
import TopsValue from "./tops-value";

export default interface TopsApiResponse {
    board: string;
    metric: string;
    period: string;
    period_label: string;
    generated_at: string;
    rows: TopsLeaderboardRow[];
    podium: TopsLeaderboardRow[];
    available_metrics: TopsPeriod[];
    available_periods: TopsPeriod[];
    filters: Record<string, TopsValue>;
    empty_message: string | null;
}
