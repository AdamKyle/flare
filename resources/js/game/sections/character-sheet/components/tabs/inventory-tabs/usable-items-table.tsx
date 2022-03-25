import React from "react";
import Table from "../../../../../components/ui/data-tables/table";
import {
    buildLimitedColumns
} from "../../../../../lib/game/character-sheet/helpers/inventory/build-inventory-table-columns";
import DropDown from "../../../../../components/ui/drop-down/drop-down";
import ActionsInterface from "../../../../../lib/game/character-sheet/helpers/inventory/actions-interface";
import InventoryDetails from "../../../../../lib/game/character-sheet/types/inventory/inventory-details";

export default class UsableItemsTable extends React.Component<any, any> implements ActionsInterface {
    constructor(props: any) {
        super(props);
    }

    useItem(id: number) {
        console.log('use item: ' + id);
    }

    useMany(ids: number[]) {
        console.log('Use many: ' + ids.join(','));
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
                    name: 'Use Many Items',
                    icon_class: 'ra ra-bubbling-potion',
                    on_click: () => this.useMany.bind(this)
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
