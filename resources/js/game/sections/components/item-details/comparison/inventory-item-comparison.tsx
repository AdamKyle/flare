import React from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import ComponentLoading from "../../../../components/ui/loading/component-loading";
import {AxiosError, AxiosResponse} from "axios";
import Ajax from "../../../../lib/ajax/ajax";
import InventoryItemComparisonState
    from "./types/inventory-item-comparison-state";
import ItemNameColorationText from "../../../../components/ui/item-name-coloration-text";
import InventoryItemComparisonProps
    from "./types/inventory-item-comparison-props";
import InventoryComparisonAdjustment
    from "./types/inventory-comparison-adjustment";
import ComparisonSection from "./comparison-section";

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

    setStatusToLoading() {
        this.setState({
            action_loading: !this.state.action_loading
        })
    }

    componentDidMount() {
        (new Ajax()).setRoute('character/'+this.props.character_id+'/inventory/comparison').setParameters({
            slot_id: this.props.slot_id,
            item_to_equip_type: this.props.item_type,
        }).doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                loading: false,
                comparison_details: result.data,
            })
        }, (error: AxiosError) => {
            console.error(error);;
        })
    }

    buildTitle() {
        if (this.state.comparison_details === null) {
            return 'Loading comparison data ...';
        }

        return (
            <ItemNameColorationText item={{
                name: this.state.comparison_details.itemToEquip.affix_name,
                type: this.state.comparison_details.itemToEquip.type,
                affix_count: this.state.comparison_details.itemToEquip.affix_count,
                is_unique: this.state.comparison_details.itemToEquip.is_unique,
                holy_stacks_applied: this.state.comparison_details.itemToEquip.holy_stacks_applied,
                is_mythic: this.state.comparison_details.itemToEquip.is_mythic,
            }} />
        )
    }

    isLargeModal() {

        if (this.state.comparison_details !== null) {
            return this.state.comparison_details.details.length === 2;
        }

        return false;
    }


    isGridSize(size: number, itemToEquip: InventoryComparisonAdjustment) {
        switch(size) {
            case 5 :
                return itemToEquip.affix_count === 0 && itemToEquip.holy_stacks_applied === 0 && !itemToEquip.is_unique
            case 7 :
                return itemToEquip.affix_count > 0 || itemToEquip.holy_stacks_applied > 0 || itemToEquip.is_unique
            default:
                return false;
        }
    }

    render() {

        if (this.props.is_dead) {
            return (
                <Dialogue is_open={this.props.is_open}
                          handle_close={this.props.manage_modal}
                          title={'You are dead'}
                          large_modal={false}
                          primary_button_disabled={false}
                >
                    <p className='text-red-700 dark:text-red-400'>And you thought dead people could manage their inventory. Go to the game tab, click revive and live again.</p>
                </Dialogue>
            )
        }

        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={this.buildTitle()}
                      large_modal={true}
                      primary_button_disabled={this.state.action_loading}
            >
                {
                    this.state.loading || this.state.comparison_details === null ?
                        <div className='p-5 mb-2'><ComponentLoading /></div>
                    :
                        <ComparisonSection
                            is_large_modal={this.isLargeModal()}
                            is_grid_size={this.isGridSize.bind(this)}
                            comparison_details={this.state.comparison_details}
                            set_action_loading={this.setStatusToLoading.bind(this)}
                            is_action_loading={this.state.action_loading}
                            update_inventory={this.props.update_inventory}
                            set_success_message={this.props.set_success_message}
                            manage_modal={this.props.manage_modal}
                            character_id={this.props.character_id}
                            dark_charts={this.props.dark_charts}
                            usable_sets={this.props.usable_sets}
                            slot_id={this.props.slot_id}
                            is_automation_running={this.props.is_automation_running}
                        />
                }
            </Dialogue>
        );
    }
}
