import TopsStatListItem from "./tops-stat-list-item";

export default interface TopsStatListProps {
    items: TopsStatListItem[];
    compact?: boolean;
    accentClassName?: string;
}
