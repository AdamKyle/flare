import React from "react";
import Table from "../../../../../components/ui/data-tables/table";
import {
    buildLimitedColumns
} from "../../../../../lib/game/character-sheet/helpers/inventory/build-inventory-table-columns";

export default class UsableItemsTable extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <Table data={this.props.usable_items} columns={buildLimitedColumns()} dark_table={this.props.dark_table}/>
        );
    }
}
