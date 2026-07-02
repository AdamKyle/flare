import {
    ExplorationFilters,
    ExplorationLogRow,
    Paginated,
} from "./exploration-monitoring";

export default interface ExplorationLogsTableProps {
    logs: Paginated<ExplorationLogRow>;
    filters: ExplorationFilters;
    onFiltersChange: (filters: ExplorationFilters) => void;
    onPageChange: (page: number) => void;
}
