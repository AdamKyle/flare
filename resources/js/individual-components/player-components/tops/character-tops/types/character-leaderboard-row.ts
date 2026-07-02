import TopsLeaderboardRow from "../../shared/types/tops-leaderboard-row";

export default interface CharacterLeaderboardRow extends TopsLeaderboardRow {
    level: number;
    xp: number;
    times_reincarnated: number;
    race: string;
    class: string;
    map: string;
    gold: number;
    online: boolean;
}
