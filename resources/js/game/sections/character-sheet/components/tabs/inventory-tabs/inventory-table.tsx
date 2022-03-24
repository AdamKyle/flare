import React from "react";
import Table from "../../../../../components/ui/data-tables/table";
import InventoryTabProps from "../../../../../lib/game/character-sheet/types/inventory-tab-props";
import {BuildInventoryTableColumns} from "../../../../../lib/game/character-sheet/helpers/inventory/build-inventory-table-columns";


export default class InventoryTable extends React.Component<InventoryTabProps, any> {
    constructor(props: InventoryTabProps) {
        super(props);
    }

    render() {
        return (
            <Table data={this.props.inventory} columns={BuildInventoryTableColumns()} dark_table={this.props.dark_table}/>
        );
    }
}
