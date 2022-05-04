import React, {Fragment} from "react";
import ItemNameColorationText from "../../../components/ui/item-name-coloration-text";
import {capitalize} from "lodash";
import InventoryComparisonAdjustment
    from "../../../lib/game/character-sheet/types/modal/inventory-comparison-adjustment";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import ComparisonSection
    from "../../character-sheet/components/modals/components/inventory-comparison/comparison-section";
import {
    watchForChatDarkModeComparisonChange,
} from "../../../lib/game/dark-mode-watcher";

export default class ItemComparison extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            comparison_details: null,
            usable_sets: [],
            action_loading: false,
            loading: true,
            dark_charts: false,
        }
    }

    componentDidMount() {
        watchForChatDarkModeComparisonChange(this);

        (new Ajax()).setRoute('character/'+this.props.character_id+'/inventory/comparison-from-chat').setParameters({
            params: {
                slot_id: this.props.slot_id,
            }
        }).doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                loading: false,
                comparison_details: result.data.comparison_data,
                usable_sets: result.data.usable_sets
            })
        }, (error: AxiosError) => {
            console.log(error);
        })
    }

    setStatusToLoading() {
        this.setState({
            action_loading: !this.state.action_loading
        })
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

        if (this.props.is_dead) {
            return (
                <Dialogue is_open={this.props.is_open}
                          handle_close={this.props.manage_modal}
                          title={'You are dead'}
                          secondary_actions={null}
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
                      secondary_actions={null}
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
                            manage_modal={this.props.manage_modal}
                            character_id={this.props.character_id}
                            dark_charts={this.state.dark_charts}
                            usable_sets={this.state.usable_sets}
                            slot_id={this.props.slot_id}
                        />
                }
            </Dialogue>
        )
    }
}