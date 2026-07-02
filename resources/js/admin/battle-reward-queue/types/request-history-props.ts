import {
    CharacterRow,
    Paginated,
    RequestFiltersType,
    RewardRequest,
} from "./reward-queue";

export default interface RequestHistoryProps {
    selectedCharacter: CharacterRow | null;
    requests: Paginated<RewardRequest>;
    filters: RequestFiltersType;
    onFiltersChange: (filters: RequestFiltersType) => void;
    onClearCharacter: () => void;
    onPageChange: (page: number) => void;
}
