export default interface TopsFilterState {
    period: string;
    metric: string;
    search: string;
    race?: string;
    class?: string;
    map?: string;
    online_only?: boolean;
}
