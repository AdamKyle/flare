export default interface PaginationControlsProps {
    currentPage: number;
    lastPage: number;
    label: string;
    onPageChange: (page: number) => void;
}
