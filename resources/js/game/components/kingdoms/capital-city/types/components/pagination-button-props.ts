export default interface PaginationButtonProps {
    page_number: number;
    current_page: number;
    on_page_change: (page: number) => void;
}
