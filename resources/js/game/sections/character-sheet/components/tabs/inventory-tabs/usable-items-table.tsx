import React from "react";
import Table from "../../../../../components/ui/data-tables/table";

const columns = [
    {
        name: 'Title',
        selector: (row: { title: any; }) => row.title,
        sortable: true,
    },
    {
        name: 'Year',
        selector: (row: { year: any; }) => row.year,
        sortable: true,
    },
];

const data = [
    {
        id: 1,
        title: 'Apples',
        year: '1988',
    },
    {
        id: 2,
        title: 'Bananas',
        year: '1984',
    },
]

export default class UsableItemsTable extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <Table data={data} columns={columns} dark_table={this.props.dark_table}/>
        );
    }
}
