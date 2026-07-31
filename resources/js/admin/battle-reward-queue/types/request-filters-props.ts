import { RequestFiltersType } from "./reward-queue";

export default interface RequestFiltersProps {
    filters: RequestFiltersType;
    onChange: (filters: RequestFiltersType) => void;
}
