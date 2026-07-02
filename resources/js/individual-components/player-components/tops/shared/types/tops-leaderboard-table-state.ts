export default interface TopsLeaderboardTableState {
    sortKey: string;
    sortDirection: "asc" | "desc";
    currentPage: number;
    perPage: number;
}
