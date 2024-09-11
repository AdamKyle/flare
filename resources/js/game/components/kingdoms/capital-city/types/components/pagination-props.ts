export default interface PaginationProps {
    current_page: number;
    total_items: number;
    items_per_page: number;
    on_page_change: (page: number) => void;
}
