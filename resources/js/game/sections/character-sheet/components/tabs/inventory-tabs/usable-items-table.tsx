import React, {Fragment} from "react";
import Table from "../../../../../components/ui/data-tables/table";
import {
    buildLimitedColumns
} from "../../../../../lib/game/character-sheet/helpers/inventory/build-inventory-table-columns";
import DropDown from "../../../../../components/ui/drop-down/drop-down";
import ActionsInterface from "../../../../../lib/game/character-sheet/helpers/inventory/actions-interface";
import InventoryDetails from "../../../../../lib/game/character-sheet/types/inventory/inventory-details";
import UsableItemTable from "../../../../../lib/game/character-sheet/types/tables/usable-items-table-props";
import MenuItemType from "../../../../../lib/ui/types/drop-down/menu-item-type";
import ListItemModal from "../../modals/components/inventory-comparison/list-item-modal";
import Ajax from "../../../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import InventoryUseItem from "../../modals/inventory-use-item";
import UsableItemsDetails from "../../../../../lib/game/character-sheet/types/inventory/usable-items-details";
import InventoryUseDetails from "../../modals/inventory-use-details";

export default class UsableItemsTable extends React.Component<UsableItemTable, any> implements ActionsInterface {
    constructor(props: UsableItemTable) {
        super(props);

        this.state = {
            show_list_modal: false,
            show_use_item_modal: false,
            show_usable_details: false,
            show_use_many: false,
            item_to_list: null,
            item_to_use: null,
        }
    }

    manageUseItem(row?: UsableItemsDetails) {
        this.setState({
            show_use_item_modal: !this.state.show_use_item_modal,
            item_to_use: typeof row !== 'undefined' ? row : null,
        })
    }

    list(listedFor: number) {

        (new Ajax()).setRoute('market-board/sell-item/' + this.props.character_id).setParameters({
            list_for: listedFor,
            slot_id: this.state.item_to_list?.slot_id,
        }).doAjaxCall('post', (result: AxiosResponse) => {
            this.setState({
                item_to_list: null,
            }, () => {
                this.props.update_inventory(result.data.inventory);

                this.props.set_success_message(result.data.message);
            })
        }, (error: AxiosError) => {

        });
    }

    destroy(row: UsableItemsDetails) {
        (new Ajax()).setRoute('character/'+this.props.character_id+'/inventory/destroy-alchemy-item').setParameters({
            slot_id: row.slot_id,
        }).doAjaxCall('post', (result: AxiosResponse) => {
            this.props.update_inventory(result.data.inventory);

            this.props.set_success_message(result.data.message);
        }, (error: AxiosError) => {

        });
    }

    manageList(item?: UsableItemsDetails) {
        this.setState({
            show_list_modal: !this.state.show_list_modal,
            item_to_list: typeof item !== 'undefined' ? item : null,
        });
    }

    actions(row: UsableItemsDetails): JSX.Element {
        const items: MenuItemType[] = [
            {
                name: 'List',
                icon_class: 'ra ra-wooden-sign',
                on_click: () => this.manageList(row)
            },
            {
                name: 'Destroy',
                icon_class: 'ra ra-bubbling-potion',
                on_click: () => this.destroy(row)
            },
        ];

        if (row.usable && !row.damages_kingdoms) {
            items.push({
                name: 'Use Item',
                icon_class: 'ra ra-bubbling-potion',
                on_click: () => this.manageUseItem(row)
            });
        }

        return (
            <DropDown menu_items={items} button_title={'Actions'}  use_relative={true}/>
        )
    }

    manageViewItem(item?: InventoryDetails | UsableItemsDetails) {
        this.setState({
            show_usable_details: !this.state.show_usable_details,
            item_to_use: typeof item !== 'undefined' ? item : null,
        });
    }

    render() {
        console.log(this.props.usable_items)
        return (
            <Fragment>

                <div className={'max-w-[290px] sm:max-w-[100%] overflow-y-hidden'}>
                    <Table data={this.props.usable_items} columns={buildLimitedColumns(this, this.manageViewItem.bind(this), true)} dark_table={this.props.dark_table}/>
                </div>

                {
                    this.state.show_list_modal && this.state.item_to_list !== null ?
                        <ListItemModal
                            is_open={this.state.show_list_modal}
                            manage_modal={this.manageList.bind(this)}
                            list_item={this.list.bind(this)}
                            item={this.state.item_to_list}
                            dark_charts={this.props.dark_table}
                        />
                    : null
                }

                {
                    this.state.show_use_item_modal && this.state.item_to_use !== null ?
                        <InventoryUseItem
                            is_open={this.state.show_use_item_modal}
                            manage_modal={this.manageUseItem.bind(this)}
                            item={this.state.item_to_use}
                            update_inventory={this.props.update_inventory}
                            set_success_message={this.props.set_success_message}
                            character_id={this.props.character_id}
                        />
                    : null
                }

                {
                    this.state.show_usable_details && this.state.item_to_use !== null ?
                        <InventoryUseDetails
                            is_open={this.state.show_usable_details}
                            manage_modal={this.manageViewItem.bind(this)}
                            item={this.state.item_to_use}
                        />
                    : null
                }


            </Fragment>

        );
    }
}
