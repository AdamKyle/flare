import React from "react";
import DataTable from "react-data-table-component";
import DataTableProps from "../../../lib/ui/types/tables/data-table-props";

export default class Table extends React.Component<DataTableProps, any> {

    constructor(props: DataTableProps) {
        super(props);
    }

    render() {
        return (
            <div className={'w-[400px] sm:w-full'}>
                <DataTable
                    columns={this.props.columns}
                    data={this.props.data}
                    theme={this.props.dark_table ? 'dark' : 'default'}
                    conditionalRowStyles={typeof this.props.conditional_row_styles === 'undefined' ? [] : this.props.conditional_row_styles}
                    pagination
                    responsive
                />
            </div>
        )
    }
}
