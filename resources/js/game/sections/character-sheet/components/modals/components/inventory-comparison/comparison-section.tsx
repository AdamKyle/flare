import React from "react";
import ItemComparisonSection from "../item-comparison-section";
import clsx from "clsx";
import PrimaryOutlineButton from "../../../../../../components/ui/buttons/primary-outline-button";
import SuccessOutlineButton from "../../../../../../components/ui/buttons/success-outline-button";
import DangerOutlineButton from "../../../../../../components/ui/buttons/danger-outline-button";
import LoadingProgressBar from "../../../../../../components/ui/progress-bars/loading-progress-bar";
import EquipModal from "./equip-modal";
import MoveItemModal from "./move-item-modal";
import SellItemModal from "./sell-item-modal";
import ListItemModal from "./list-item-modal";
import InventoryComparisonAdjustment
    from "../../../../../../lib/game/character-sheet/types/modal/inventory-comparison-adjustment";
import InventoryComparisonActions from "../../../../../../lib/game/character-sheet/ajax/inventory-comparison-actions";
import WarningAlert from "../../../../../../components/ui/alerts/simple-alerts/warning-alert";

export default class ComparisonSection extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            show_equip_modal: false,
            show_move_modal: false,
            show_sell_modal: false,
            show_list_item_modal: false,
            item_to_sell: null,
        }
    }

    manageEquipModal() {
        this.setState({
            show_equip_modal: !this.state.show_equip_modal
        })
    }

    manageMoveModalModal() {
        this.setState({
            show_move_modal: !this.state.show_move_modal
        })
    }

    manageSellModal(item?: InventoryComparisonAdjustment) {
        this.setState({
            show_sell_modal: !this.state.show_sell_modal,
            item_to_sell: typeof item === 'undefined' ? null : item,
        })
    }

    manageListItemModal(item?: InventoryComparisonAdjustment) {
        this.setState({
            show_list_item_modal: !this.state.show_list_item_modal,
            item_to_sell: typeof item === 'undefined' ? null : item,
        })
    }

    equipItem(type: string, position?: string) {

        this.props.set_action_loading();

        const params = {
            position: position,
            slot_id: this.props.slot_id,
            equip_type: type,
        };

        (new InventoryComparisonActions()).equipItem(this, params);
    }

    moveItem(setId: number) {

        this.props.set_action_loading();

        const params = {
            move_to_set: setId,
            slot_id: this.props.comparison_details.itemToEquip.slot_id,
        };

        (new InventoryComparisonActions()).moveItem(this, params);
    }

    sellItem() {
        this.props.set_action_loading();

        const params = {
            slot_id: this.props.comparison_details.itemToEquip.slot_id,
        };

        (new InventoryComparisonActions()).sellItem(this, params);
    }

    listItem(price: number) {
        this.props.set_action_loading();

        const params = {
            list_for: price,
            slot_id: this.props.comparison_details.itemToEquip.slot_id,
        };

        (new InventoryComparisonActions()).listItem(this, params);
    }


    disenchantItem() {
        this.props.set_action_loading();

        (new InventoryComparisonActions()).disenchantItem(this);
    }

    destroyItem() {

        this.props.set_action_loading();

        const params = {
            slot_id: this.props.comparison_details.itemToEquip.slot_id,
        };

        (new InventoryComparisonActions()).destroyItem(this, params);
    }

    showReplaceMessage() {
        if (!this.props.is_automation_running) {
            const twoHandedEquipped = (this.props.comparison_details.hammerEquipped || this.props.comparison_details.bowEquipped || this.props.comparison_details.staveEquipped);
            return ['hammer', 'bow', 'stave'].includes(this.props.comparison_details.itemToEquip.type) && twoHandedEquipped;
        }
    }

    render() {
        return (
            <div className='p-5'>
                {
                    this.props.is_automation_running ?
                        <WarningAlert additional_css={'mb-4'}>
                            <p>You are exploring. Some actions have been disabled.</p>
                        </WarningAlert>
                    : null
                }
                {
                    this.showReplaceMessage() ?
                        <WarningAlert additional_css={'mb-4'}>
                            <p>The item you are looking at will replace the current two handed weapon you have equipped.</p>
                        </WarningAlert>
                    : null
                }

                <ItemComparisonSection comparison_details={this.props.comparison_details} view_port={this.props.view_port}/>
                <div className='border-b-2 mt-6 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <div className={clsx(
                    'mt-6 grid grid-cols-1 w-full gap-2 md:m-auto',
                    {
                        'md:w-3/4': this.props.is_large_modal,
                        'md:grid-cols-6': this.props.is_grid_size(6, this.props.comparison_details.itemToEquip),
                        'md:grid-cols-4': this.props.is_grid_size(4, this.props.comparison_details.itemToEquip),
                        'hidden': this.props.comparison_details.itemToEquip.type === 'quest',
                    }
                )}>
                    <PrimaryOutlineButton button_label={'Equip'} on_click={this.manageEquipModal.bind(this)} disabled={this.props.is_action_loading || this.props.is_automation_running}/>
                    <PrimaryOutlineButton button_label={'Move'} on_click={this.manageMoveModalModal.bind(this)} disabled={this.props.is_action_loading}/>
                    <SuccessOutlineButton button_label={'Sell'} on_click={() => this.manageSellModal(this.props.comparison_details.itemToEquip)} disabled={this.props.is_action_loading}/>

                    {
                        this.props.comparison_details.itemToEquip.affix_count > 0 || this.props.comparison_details.itemToEquip.holy_stacks_applied > 0 ?
                            <SuccessOutlineButton button_label={'List'}
                                                  on_click={() => this.manageListItemModal(this.props.comparison_details.itemToEquip)}
                                                  disabled={this.props.is_action_loading || this.props.is_automation_running}/>
                        : null
                    }

                    {
                        this.props.comparison_details.itemToEquip.affix_count > 0 ?
                            <DangerOutlineButton button_label={'Disenchant'} on_click={this.disenchantItem.bind(this)} disabled={this.props.is_action_loading}/>
                        : null
                    }

                    <DangerOutlineButton button_label={'Destroy'} on_click={this.destroyItem.bind(this)} disabled={this.props.is_action_loading}/>
                </div>

                {
                    this.props.is_action_loading ?
                        <LoadingProgressBar />
                    : null
                }

                {
                    this.state.show_equip_modal ?
                        <EquipModal is_open={this.state.show_equip_modal}
                                    manage_modal={this.manageEquipModal.bind(this)}
                                    item_to_equip={this.props.comparison_details.itemToEquip}
                                    equip_item={this.equipItem.bind(this)}
                                    is_bow_equipped={this.props.comparison_details.bowEquipped}
                                    is_hammer_equipped={this.props.comparison_details.hammerEquipped}
                                    is_stave_equipped={this.props.comparison_details.staveEquipped}
                        />
                    : null
                }

                {
                    this.state.show_move_modal ?
                        <MoveItemModal is_open={this.state.show_move_modal}
                                       manage_modal={this.manageMoveModalModal.bind(this)}
                                       usable_sets={this.props.usable_sets}
                                       move_item={this.moveItem.bind(this)}
                        />
                    : null
                }

                {
                    this.state.show_sell_modal && this.state.item_to_sell !== null ?
                        <SellItemModal is_open={this.state.show_sell_modal}
                                       manage_modal={this.manageSellModal.bind(this)}
                                       sell_item={this.sellItem.bind(this)}
                                       item={this.state.item_to_sell}
                        />
                    : null
                }

                {
                    this.state.show_list_item_modal ?
                        <ListItemModal
                            is_open={this.state.show_list_item_modal}
                            manage_modal={this.manageListItemModal.bind(this)}
                            list_item={this.listItem.bind(this)}
                            item={this.state.item_to_sell}
                            dark_charts={this.props.dark_charts}
                        />
                    : null
                }
            </div>
        )
    }
}
