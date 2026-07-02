import TopsDisplayField from "./tops-display-field";
import TopsLeaderboardRow from "./tops-leaderboard-row";

export default interface TopsLeaderboardTableProps {
    rows: TopsLeaderboardRow[];
    fields: TopsDisplayField[];
    minTableWidth?: string;
}
