import TopsApiResponse from "../../shared/types/tops-api-response";

export default interface DelveLeaderboardProps {
    leaderboard: TopsApiResponse | null;
    loading: boolean;
    errorMessage: string | null;
    period: string;
    metric: string;
    search: string;
    onPeriodChange: (value: string) => void;
    onMetricChange: (value: string) => void;
    onSearch: (value: string) => void;
}
