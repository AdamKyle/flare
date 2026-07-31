import TopsApiResponse from "../../shared/types/tops-api-response";

export default interface CharacterLeaderboardProps {
    leaderboard: TopsApiResponse | null;
    loading: boolean;
    errorMessage: string | null;
    period: string;
    metric: string;
    search: string;
    onlineOnly: boolean;
    onPeriodChange: (value: string) => void;
    onMetricChange: (value: string) => void;
    onSearch: (value: string) => void;
    onOnlineOnly: (value: boolean) => void;
}
