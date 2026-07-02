import { CharacterRow, Paginated } from "./reward-queue";

export default interface CharacterQueueTableProps {
    characters: Paginated<CharacterRow>;
    onSelect: (character: CharacterRow) => void;
    onPageChange: (page: number) => void;
}
