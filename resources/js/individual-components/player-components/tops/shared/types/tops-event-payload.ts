import TopsApiResponse from "./tops-api-response";
import TopsProfile from "./tops-profile";

export default interface TopsEventPayload {
    leaderboard?: TopsApiResponse;
    profile?: TopsProfile;
}
