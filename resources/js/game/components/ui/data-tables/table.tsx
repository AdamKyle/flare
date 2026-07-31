import React from "react";
import DataTable from "react-data-table-component";
import DataTableProps from "../../../lib/ui/types/tables/data-table-props";

export default class Table extends React.Component<DataTableProps, any> {
    constructor(props: DataTableProps) {
        super(props);
    }

    render() {
        return (
            <div>
                <DataTable
                    columns={this.props.columns}
                    data={this.props.data}
                    theme={this.props.dark_table ? "dark" : "default"}
                    conditionalRowStyles={
                        typeof this.props.conditional_row_styles === "undefined"
                            ? []
                            : this.props.conditional_row_styles
                    }
                    pagination
                    paginationPerPage={this.props.pagination_per_page}
                    paginationRowsPerPageOptions={
                        this.props.pagination_rows_per_page_options
                    }
                    expandableRows={this.props.expandable_rows}
                    expandOnRowClicked={this.props.expand_on_row_clicked}
                    expandableRowsHideExpander={
                        this.props.expandable_rows_hide_expander
                    }
                    expandableRowExpanded={this.props.expandable_row_expanded}
                    expandableRowsComponent={
                        this.props.expandable_rows_component
                    }
                    onRowExpandToggled={this.props.on_row_expand_toggled}
                    onRowClicked={this.props.on_row_clicked}
                    onChangePage={this.props.on_change_page}
                    responsive
                />
            </div>
        );
    }
}
