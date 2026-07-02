import TopsApiResponse from "../../shared/types/tops-api-response";
import CharacterProfile from "./character-profile";

export default interface CharacterTopsState {
    loading: boolean;
    profile_loading: boolean;
    error_message: string | null;
    leaderboard: TopsApiResponse | null;
    profile: CharacterProfile | null;
    active_profile_tab: string;
    period: string;
    metric: string;
    search: string;
    online_only: boolean;
}
