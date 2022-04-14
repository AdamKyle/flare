import React from "react";
import Table from "../../../../../components/ui/data-tables/table";
import {
    buildLimitedColumns
} from "../../../../../lib/game/character-sheet/helpers/inventory/build-inventory-table-columns";
import DropDown from "../../../../../components/ui/drop-down/drop-down";
import ActionsInterface from "../../../../../lib/game/character-sheet/helpers/inventory/actions-interface";
import InventoryDetails from "../../../../../lib/game/character-sheet/types/inventory/inventory-details";
import UsableItemTable from "../../../../../lib/game/character-sheet/types/tables/usable-items-table-props";

export default class UsableItemsTable extends React.Component<UsableItemTable, any> implements ActionsInterface {
    constructor(props: UsableItemTable) {
        super(props);
    }

    useItem(id: number) {
        console.log('use item: ' + id);
    }

    list() {

    }

    destroy() {

    }

    actions(row: InventoryDetails): JSX.Element {
        return (
            <DropDown menu_items={[
                {
                    name: 'Use Item',
                    icon_class: 'ra ra-bubbling-potion',
                    on_click: () => this.useItem.bind(this)
                },
                {
                    name: 'List',
                    icon_class: 'ra ra-wooden-sign',
                    on_click: () => this.list.bind(this)
                },
                {
                    name: 'Destroy',
                    icon_class: 'ra ra-bubbling-potion',
                    on_click: () => this.destroy.bind(this)
                },
            ]} button_title={'Use Options'} />
        )
    }

    render() {
        return (
            <Table data={this.props.usable_items} columns={buildLimitedColumns(this)} dark_table={this.props.dark_table}/>
        );
    }
}