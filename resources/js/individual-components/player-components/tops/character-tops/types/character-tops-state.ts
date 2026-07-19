import TopsApiResponse from "../../shared/types/tops-api-response";
import CharacterProfile from "./character-profile";
import {
    DeferredProfileSectionLoading,
    DeferredProfileSectionErrors,
} from "./deferred-profile-section-state";

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
    deferred_loading: DeferredProfileSectionLoading;
    deferred_errors: DeferredProfileSectionErrors;
}
