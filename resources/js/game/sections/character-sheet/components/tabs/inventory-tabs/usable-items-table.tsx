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
import ListItemModal from "../../../../../components/modals/chat-item-comparison/action-modals/list-item-modal";
import Ajax from "../../../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import InventoryUseItem from "../../modals/inventory-use-item";
import UsableItemsDetails from "../../../../../lib/game/character-sheet/types/inventory/usable-items-details";
import InventoryUseDetails from "../../modals/inventory-use-details";
import InventoryActionConfirmationModal from "../../modals/inventory-action-confirmation-modal";

export default class UsableItemsTable extends React.Component<UsableItemTable, any> implements ActionsInterface {
    constructor(props: UsableItemTable) {
        super(props);

        this.state = {
            show_list_modal: false,
            show_use_item_modal: false,
            show_usable_details: false,
            show_use_many: false,
            show_destroy_item: false,
            item_to_list: null,
            item_to_use: null,
            item_to_destroy_name: null,
            item_slot_id_to_delete: null,
        }
    }

    manageUseItem(row: UsableItemsDetails) {
        this.setState({
            show_use_item_modal: !this.state.show_use_item_modal,
            item_to_use: row,
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
        this.setState({
            show_destroy_item: !this.state.show_destroy_item,
            item_to_destroy_name: row.item_name,
            item_slot_id_to_delete: row.slot_id,
        });
    }

    showDestroyConfirmation() {
        this.setState({
            show_destroy_item: !this.state.show_destroy_item,
            item_to_destroy_name: null,
            item_slot_id_to_delete: null,
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
        return (
            <Fragment>

                <div className={'max-w-[390px] md:max-w-full overflow-y-hidden'}>
                    <Table data={this.props.usable_items} columns={buildLimitedColumns(this, this.manageViewItem.bind(this), true)} dark_table={this.props.dark_table}/>
                </div>

                {
                    this.state.show_destroy_item ?
                        <InventoryActionConfirmationModal
                            is_open={this.state.show_destroy_item}
                            manage_modal={this.showDestroyConfirmation.bind(this)}
                            title={"Destroy " + this.state.item_to_destroy_name}
                            url={
                                'character/'+ this.props.character_id +'/inventory/destroy-alchemy-item'
                            }
                            ajax_params={{
                                slot_id: this.state.item_slot_id_to_delete
                            }}
                            update_inventory={this.props.update_inventory}
                            set_success_message={this.props.set_success_message}
                        >
                            <p>
                                Are you sure you want to do this? This action will
                                destroy the selected item from your usable inventory. You cannot undo
                                this action.
                            </p>
                        </InventoryActionConfirmationModal>
                    : null
                }

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
