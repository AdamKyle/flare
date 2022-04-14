import React, {Fragment} from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import ComponentLoading from "../../../../components/ui/loading/component-loading";
import {AxiosError, AxiosResponse} from "axios";
import Ajax from "../../../../lib/ajax/ajax";
import InventoryItemComparisonState
    from "../../../../lib/game/character-sheet/types/modal/inventory-item-comparison-state";
import ItemNameColorationText from "../../../../components/ui/item-name-coloration-text";
import {capitalize} from "lodash";
import PrimaryOutlineButton from "../../../../components/ui/buttons/primary-outline-button";
import SuccessOutlineButton from "../../../../components/ui/buttons/success-outline-button";
import DangerOutlineButton from "../../../../components/ui/buttons/danger-outline-button";
import clsx from "clsx";
import EquipModal from "./components/inventory-comparison/equip-modal";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import InventoryItemComparisonProps
    from "../../../../lib/game/character-sheet/types/modal/inventory-item-comparison-props";
import ItemComparisonSection from "./components/item-comparison-section";
import MoveItemModal from "./components/inventory-comparison/move-item-modal";
import InventoryComparisonAdjustment
    from "../../../../lib/game/character-sheet/types/modal/inventory-comparison-adjustment";
import SellItemModal from "./components/inventory-comparison/sell-item-modal";
import ListItemModal from "./components/inventory-comparison/list-item-modal";

export default class InventoryItemComparison extends React.Component<InventoryItemComparisonProps, InventoryItemComparisonState> {

    private tabs: {name: string, key: string}[];

    constructor(props: InventoryItemComparisonProps) {
        super(props);

        this.tabs = [{
            key: 'general',
            name: 'General'
        }, {
            key: 'comparison',
            name: 'Comparison',
        }]

        this.state = {
            loading: true,
            action_loading: false,
            comparison_details: null,
            show_equip_modal: false,
            show_move_modal: false,
            show_sell_modal: false,
            show_list_item_modal: false,
            item_to_sell: null,
        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('character/'+this.props.character_id+'/inventory/comparison').setParameters({
            params: {
                slot_id: this.props.slot_id,
                item_to_equip_type: this.props.item_type,
            }
        }).doAjaxCall('get', (result: AxiosResponse) => {
            console.log(result.data);
            this.setState({
                loading: false,
                comparison_details: result.data,
            })
        }, (error: AxiosError) => {
            console.log(error);
        })
    }

    equipItem(type: string, position?: string) {

        this.setState({
            action_loading: true
        });

        (new Ajax()).setRoute('character/'+this.props.character_id+'/inventory/equip-item').setParameters({
            position: position,
            slot_id: this.props.slot_id,
            equip_type: type,
        }).doAjaxCall('post', (result: AxiosResponse) => {
            this.setState({
                action_loading: false,
            }, () => {
                this.props.update_inventory(result.data.inventory);

                this.props.set_success_message(result.data.message);

                this.props.manage_modal();
            })
        }, (error: AxiosError) => {

        });
    }

    moveItem(setId: number) {

        this.setState({
            action_loading: true
        });

        (new Ajax()).setRoute('character/'+this.props.character_id+'/inventory/move-to-set').setParameters({
            move_to_set: setId,
            slot_id: this.state.comparison_details?.itemToEquip.slot_id,
        }).doAjaxCall('post', (result: AxiosResponse) => {
            this.setState({
                action_loading: false,
            }, () => {
                this.props.update_inventory(result.data.inventory);

                this.props.set_success_message(result.data.message);

                this.props.manage_modal();
            })
        }, (error: AxiosError) => {

        });
    }

    sellItem() {
        this.setState({
            action_loading: true
        });

        (new Ajax()).setRoute('character/'+this.props.character_id+'/inventory/sell-item').setParameters({
            slot_id: this.state.comparison_details?.itemToEquip.slot_id,
        }).doAjaxCall('post', (result: AxiosResponse) => {
            this.setState({
                action_loading: false,
            }, () => {
                this.props.update_inventory(result.data.inventory);

                this.props.set_success_message(result.data.message);

                this.props.manage_modal();
            })
        }, (error: AxiosError) => {

        });
    }

    listItem() {

    }

    buildTitle() {
        if (this.state.comparison_details === null) {
            return 'Loading comparison data ...';
        }

        return (
            <Fragment>
            <ItemNameColorationText item={{
                name: this.state.comparison_details.itemToEquip.affix_name,
                type: this.state.comparison_details.itemToEquip.type,
                affix_count: this.state.comparison_details.itemToEquip.affix_count,
                is_unique: this.state.comparison_details.itemToEquip.is_unique,
                holy_stacks_applied: this.state.comparison_details.itemToEquip.holy_stacks_applied,
            }} /> <span className='pl-3'>(Type: {capitalize(this.state.comparison_details.itemToEquip.type)})</span>
            </Fragment>
        )
    }

    isLargeModal() {

        if (this.state.comparison_details !== null) {
            return this.state.comparison_details.details.length === 2;
        }

        return false;
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

    stubbedClick(){}

    isGridSize(size: number, itemToEquip: InventoryComparisonAdjustment) {
        switch(size) {
            case 4 :
                return itemToEquip.affix_count === 0 && itemToEquip.holy_stacks_applied === 0 && !itemToEquip.is_unique
            case 6 :
                return itemToEquip.affix_count > 0 || itemToEquip.holy_stacks_applied > 0 || itemToEquip.is_unique
            default:
                return false;
        }
    }

    render() {

        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={this.buildTitle()}
                      secondary_actions={null}
                      large_modal={true}
                      primary_button_disabled={this.state.action_loading}
            >
                {
                    this.state.loading || this.state.comparison_details === null ?
                        <div className='p-5 mb-2'><ComponentLoading /></div>
                    :
                        <div className='p-5'>
                            <ItemComparisonSection comparison_details={this.state.comparison_details} />
                            <div className='border-b-2 mt-6 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <div className={clsx(
                                'mt-6 grid grid-cols-1 w-full gap-2 md:m-auto',
                                {
                                    'md:w-3/4': this.isLargeModal(),
                                    'md:grid-cols-6': this.isGridSize(6, this.state.comparison_details.itemToEquip),
                                    'md:grid-cols-4': this.isGridSize(4, this.state.comparison_details.itemToEquip),
                                }
                            )}>
                                <PrimaryOutlineButton button_label={'Equip'} on_click={this.manageEquipModal.bind(this)} disabled={this.state.action_loading}/>
                                <PrimaryOutlineButton button_label={'Move'} on_click={this.manageMoveModalModal.bind(this)} disabled={this.state.action_loading}/>
                                <SuccessOutlineButton button_label={'Sell'} on_click={() => this.manageSellModal(this.state.comparison_details?.itemToEquip)} disabled={this.state.action_loading}/>

                                {
                                    this.state.comparison_details.itemToEquip.affix_count > 0 || this.state.comparison_details.itemToEquip.holy_stacks_applied > 0 ?
                                        <SuccessOutlineButton button_label={'List'}
                                                              on_click={() => this.manageListItemModal(this.state.comparison_details?.itemToEquip)}
                                                              disabled={this.state.action_loading}/>
                                        : null
                                }

                                {
                                    this.state.comparison_details.itemToEquip.affix_count > 0 ?
                                        <DangerOutlineButton button_label={'Disenchant'} on_click={this.stubbedClick.bind(this)} disabled={this.state.action_loading}/>
                                    : null
                                }

                                <DangerOutlineButton button_label={'Destroy'} on_click={this.stubbedClick.bind(this)} disabled={this.state.action_loading}/>
                            </div>

                            {
                                this.state.action_loading ?
                                    <LoadingProgressBar />
                                    : null
                            }

                            {
                                this.state.show_equip_modal ?
                                    <EquipModal is_open={this.state.show_equip_modal}
                                                manage_modal={this.manageEquipModal.bind(this)}
                                                item_to_equip={this.state.comparison_details.itemToEquip}
                                                equip_item={this.equipItem.bind(this)}
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
                }
            </Dialogue>
        );
    }
}
