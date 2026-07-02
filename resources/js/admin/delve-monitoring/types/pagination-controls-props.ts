export default interface PaginationControlsProps {
    currentPage: number;
    lastPage: number;
    onPageChange: (page: number) => void;
}
