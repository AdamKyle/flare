import DataTableBaseData from "./data-table-base-data";
import ConditionalDataTableRowsStyling from "./conditional-data-table-rows-styling";

export default interface DataTableProps {
    columns: DataTableBaseData[] | [];
    data: any[] | [];
    dark_table: boolean;
    conditional_row_styles?: ConditionalDataTableRowsStyling[];
    pagination_per_page?: number;
    pagination_rows_per_page_options?: number[];
}
