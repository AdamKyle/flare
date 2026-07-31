import DataTableBaseData from "./data-table-base-data";
import ConditionalDataTableRowsStyling from "./conditional-data-table-rows-styling";
import React from "react";

export default interface DataTableProps {
    columns: DataTableBaseData[] | [];
    data: any[] | [];
    dark_table: boolean;
    conditional_row_styles?: ConditionalDataTableRowsStyling[];
    pagination_per_page?: number;
    pagination_rows_per_page_options?: number[];
    expandable_rows?: boolean;
    expand_on_row_clicked?: boolean;
    expandable_rows_hide_expander?: boolean;
    expandable_row_expanded?: (row: any) => boolean;
    expandable_rows_component?: React.ComponentType<{ data: any }>;
    on_row_expand_toggled?: (expanded: boolean, row: any) => void;
    on_row_clicked?: (row: any) => void;
    on_change_page?: (page: number, totalRows: number) => void;
}
