import React from "react";
import DataTable from "react-data-table-component";

export default class Table extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <div className={'w-[400px] sm:w-full'}>
                <DataTable
                    columns={this.props.columns}
                    data={this.props.data}
                    theme={this.props.dark_table ? 'dark' : 'default'}
                    pagination
                    responsive
                />
            </div>
        )
    }
}
