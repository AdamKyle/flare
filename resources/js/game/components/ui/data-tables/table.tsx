import React from "react";
import DataTable from "react-data-table-component";
import DataTableProps from "../../../lib/ui/types/tables/data-table-props";

export default class Table extends React.Component<DataTableProps, any> {
    constructor(props: DataTableProps) {
        super(props);
    }

    render() {
        return (
            <div className="h-full flex flex-col">
                <DataTable
                    className="h-full flex flex-col"
                    columns={this.props.columns}
                    data={this.props.data}
                    theme={this.props.dark_table ? "dark" : "default"}
                    conditionalRowStyles={
                        typeof this.props.conditional_row_styles === "undefined"
                            ? []
                            : this.props.conditional_row_styles
                    }
                    customStyles={{
                        tableWrapper: {
                            style: {
                                flex: "1 1 auto",
                            },
                        },
                    }}
                    pagination
                    paginationPerPage={this.props.pagination_per_page}
                    paginationRowsPerPageOptions={
                        this.props.pagination_rows_per_page_options
                    }
                    responsive
                />
            </div>
        );
    }
}
