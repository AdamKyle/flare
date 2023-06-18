import React, {Fragment} from "react";
import Table from "../../../../../components/ui/data-tables/table";
import InventoryTabProps from "../../../../../lib/game/character-sheet/types/inventory-tab-props";
import {BuildInventoryTableColumns} from "../../../../../lib/game/character-sheet/helpers/inventory/build-inventory-table-columns";
import InventoryDetails from "../../../../../lib/game/character-sheet/types/inventory/inventory-details";
import InventoryItemComparison from "../../../../components/item-details/comparison/inventory-item-comparison";
import InventoryItemsTableState from "../../../../../lib/game/character-sheet/types/tables/inventory-items-table-state";
import UsableItemsDetails from "../../../../../lib/game/character-sheet/types/inventory/usable-items-details";
import InfoAlert from "../../../../../components/ui/alerts/simple-alerts/info-alert";

export default class InventoryTable extends React.Component<InventoryTabProps, InventoryItemsTableState> {

    constructor(props: InventoryTabProps) {
        super(props);

        this.state = {
            view_comparison: false,
            slot_id: 0,
            item_type: '',
        }
    }

    viewItem(item?: InventoryDetails | UsableItemsDetails) {
        this.setState({
            view_comparison: true,
            slot_id: typeof item !== 'undefined' ? item.slot_id : 0,
            item_type: typeof item !== 'undefined' ? item.type : '',
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
                <InfoAlert additional_css={'mt-4 mb-4'}>
                    Click the item name to get additional actions. This table only sometimes updates automatically, such as with disenchanting mass items.
                </InfoAlert>
                <div className={'max-w-[290px] sm:max-w-[100%] overflow-x-hidden'}>
                    <Table data={this.props.inventory} columns={BuildInventoryTableColumns(undefined, this.viewItem.bind(this), this.props.manage_skills)} dark_table={this.props.dark_table}/>
                </div>

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
                            usable_sets={this.props.usable_sets}
                            set_success_message={this.props.set_success_message}
                            dark_charts={this.props.dark_table}
                            is_dead={this.props.is_dead}
                            is_automation_running={this.props.is_automation_running}
                        />
                        : null
                }
            </Fragment>
        );
    }
}
