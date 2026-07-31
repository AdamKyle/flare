export default interface TopsFiltersProps {
    search: string;
    onlineOnly?: boolean;
    showOnlineOnly?: boolean;
    onSearch: (value: string) => void;
    onOnlineOnly?: (value: boolean) => void;
}
