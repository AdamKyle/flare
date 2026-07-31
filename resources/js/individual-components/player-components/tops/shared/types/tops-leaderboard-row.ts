import TopsValue from "./tops-value";

export default interface TopsLeaderboardRow {
    rank: number;
    character_id: number | null;
    character_name: string | null;
    character_profile_url: string | null;
    [key: string]: TopsValue;
}
