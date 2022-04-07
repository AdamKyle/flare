import React, {Fragment, MouseEventHandler} from "react";
import Table from "../../../../../components/ui/data-tables/table";
import InventoryTabProps from "../../../../../lib/game/character-sheet/types/inventory-tab-props";
import {BuildInventoryTableColumns} from "../../../../../lib/game/character-sheet/helpers/inventory/build-inventory-table-columns";
import ActionsInterface from "../../../../../lib/game/character-sheet/helpers/inventory/actions-interface";
import InventoryDetails from "../../../../../lib/game/character-sheet/types/inventory/inventory-details";
import PrimaryButton from "../../../../../components/ui/buttons/primary-button";
import DangerButton from "../../../../../components/ui/buttons/danger-button";
import OrangeButton from "../../../../../components/ui/buttons/orange-button";
import DropDown from "../../../../../components/ui/drop-down/drop-down";
import InventoryItemComparison from "../../modals/inventory-item-comparison";


export default class InventoryTable extends React.Component<InventoryTabProps, any> {

    constructor(props: InventoryTabProps) {
        super(props);

        this.state = {
            view_comparison: false,
            slot_id: 0,
            item_type: '',
        }
    }

    viewItem(item: InventoryDetails) {
        this.setState({
            view_comparison: true,
            slot_id: item.id,
            item_type: item.type,
        });
    }

    closeViewItem() {
        this.setState({
            view_comparison: false,
            slot_id: 0,
            item_type: '',
        });
    }

    render() {
        return (
            <Fragment>
                <Table data={this.props.inventory} columns={BuildInventoryTableColumns(undefined, this.viewItem.bind(this))} dark_table={this.props.dark_table}/>

                {
                    this.state.view_comparison ?
                        <InventoryItemComparison
                            is_open={this.state.view_comparison}
                            manage_modal={this.closeViewItem.bind(this)}
                            title={'Comparison'}
                            slot_id={this.state.slot_id}
                            item_type={this.state.item_type}
                            character_id={this.props.character_id}
                            update_inventory={this.props.update_inventory}
                        />
                    : null
                }
            </Fragment>
        );
    }
}
