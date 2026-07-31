import TopsApiResponse from "../../shared/types/tops-api-response";

export default interface FactionLoyaltyTopsState {
    loading: boolean;
    error_message: string | null;
    leaderboard: TopsApiResponse | null;
    period: string;
    metric: string;
    search: string;
}
