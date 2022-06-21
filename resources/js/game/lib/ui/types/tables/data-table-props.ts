import DataTableBaseData from "./data-table-base-data";
import ConditionalRowStyles from "./conditional-row-styles";

export default interface DataTableProps {

    columns: DataTableBaseData[] | [];
    data: any[]|[];
    dark_table: boolean;
    conditional_row_styles?: ConditionalRowStyles[];
}
