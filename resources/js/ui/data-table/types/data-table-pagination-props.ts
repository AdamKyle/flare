export default interface DataTablePaginationProps {
  current_page: number;
  total_pages: number;
  total_records: number;
  on_page_change: (page: number) => void;
}
