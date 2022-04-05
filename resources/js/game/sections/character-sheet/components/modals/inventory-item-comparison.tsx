import React, {Fragment} from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import {AdditionalInfoModalProps} from "../../../../lib/game/character-sheet/types/modal/additional-info-modal-props";
import Tabs from "../../../../components/ui/tabs/tabs";
import TabPanel from "../../../../components/ui/tabs/tab-panel";
import {formatNumber} from "../../../../lib/game/format-number";
import ComponentLoading from "../../../../components/ui/loading/component-loading";
import {AxiosError, AxiosResponse} from "axios";
import Ajax from "../../../../lib/ajax/ajax";
import InventoryItemComparisonState
    from "../../../../lib/game/character-sheet/types/modal/inventory-item-comparison-state";
import ItemNameColorationText from "../../../../components/ui/item-name-coloration-text";
import InventoryComparisonAdjustment
    from "../../../../lib/game/character-sheet/types/modal/inventory-comparison-adjustment";
import {capitalize} from "lodash";
import PrimaryOutlineButton from "../../../../components/ui/buttons/primary-outline-button";
import SuccessOutlineButton from "../../../../components/ui/buttons/success-outline-button";
import DangerOutlineButton from "../../../../components/ui/buttons/danger-outline-button";
import clsx from "clsx";

export default class InventoryItemComparison extends React.Component<any, InventoryItemComparisonState> {

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            comparison_details: null,
        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('character/'+this.props.character_id+'/inventory/comparison').setParameters({
            params: {
                slot_id: this.props.slot_id,
                item_to_equip_type: this.props.item_type,
            }
        }).doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                loading: false,
                comparison_details: result.data,
            })
        }, (error: AxiosError) => {
            console.log(error);
        })
    }

    isItemBetter() {

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

    renderChange(details: InventoryComparisonAdjustment, itemToEquip?: InventoryComparisonAdjustment) {
        const invalidFields = ['id', 'min_cost', 'skill_level_req', 'skill_level_trivial', 'holy_level', 'holy_stack_devouring_darkness', 'holy_stack_stat_bonus', 'holy_stacks', 'holy_stacks_applied', 'cost'];

        let elements = Object.keys(details).map((key) => {
            if (!invalidFields.includes(key)) {
                if (details[key] > 0) {
                    return (
                        <Fragment>
                            <dt>{capitalize(key.split('_').join(' '))}</dt>
                            <dd>{this.renderPercent(details[key])}</dd>
                        </Fragment>
                    );
                }
            }
        }).filter((e: any) => typeof e !== 'undefined');

        if (elements.length === 0 && typeof itemToEquip !== 'undefined') {
            return (
                <Fragment>
                    <dl>
                        {this.renderChange(itemToEquip)}
                    </dl>
                </Fragment>
            );
        }

        return (
            <Fragment>
                <dl>
                    {elements}
                </dl>
            </Fragment>
        )
    }

    renderPercent(value: any) {
        if (value % 1 !== 0) {
            return (value * 100).toFixed(2) + '%'
        }

        return formatNumber(value);
    }

    renderItemToEquip(itemToEquip: InventoryComparisonAdjustment) {
        const invalidFields = ['id', 'min_cost', 'skill_level_req', 'skill_level_trivial', 'holy_level', 'holy_stack_devouring_darkness', 'holy_stack_stat_bonus', 'holy_stacks', 'holy_stacks_applied', 'cost'];

        return Object.keys(itemToEquip).map((key) => {
            if (!invalidFields.includes(key)) {
                if (itemToEquip[key] > 0) {
                    return (
                        <Fragment>
                            <dt>{capitalize(key.split('_').join(' '))}</dt>
                            <dd>{this.renderPercent(itemToEquip[key])}</dd>
                        </Fragment>
                    );
                }
            }
        });
    }

    isLargeModal() {

        if (this.state.comparison_details !== null) {
            return this.state.comparison_details.details.length === 2;
        }

        return false;
    }

    stubbedClick(){}

    render() {

        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={this.buildTitle()}
                      secondary_actions={null}
                      large_modal={this.isLargeModal()}
            >
                {
                    this.state.loading || this.state.comparison_details === null ?
                        <div className='p-5 mb-2'><ComponentLoading /></div>
                    :
                        <div className='p-5'>
                            {
                                this.state.comparison_details.details.length > 0 ?
                                    <div className='grid w-full md:grid-cols-2 md:w-3/4 md:m-auto'>
                                        <div>
                                            {this.renderChange(this.state.comparison_details.details[0], this.state.comparison_details.itemToEquip)}
                                        </div>
                                        <div className='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-3 mt-6'></div>
                                        <div>
                                            {this.renderChange(this.state.comparison_details.details[1], this.state.comparison_details.itemToEquip)}
                                        </div>
                                    </div>
                                :
                                    <div>
                                        <dl>
                                            {this.renderItemToEquip(this.state.comparison_details.itemToEquip)}
                                        </dl>
                                    </div>
                            }
                            <div className='border-b-2 mt-6 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <div className={clsx(
                                'mt-6 grid grid-cols-1 w-full md:grid-cols-6 gap-2 md:m-auto',
                                {
                                    'md:w-3/4': this.isLargeModal()
                                }
                            )}>
                                <PrimaryOutlineButton button_label={'Equip'} on_click={this.stubbedClick.bind(this)} />
                                <PrimaryOutlineButton button_label={'Move'} on_click={this.stubbedClick.bind(this)} />
                                <SuccessOutlineButton button_label={'Sell'} on_click={this.stubbedClick.bind(this)} />
                                <SuccessOutlineButton button_label={'List'} on_click={this.stubbedClick.bind(this)} />
                                <DangerOutlineButton button_label={'Disenchant'} on_click={this.stubbedClick.bind(this)} />
                                <DangerOutlineButton button_label={'Destroy'} on_click={this.stubbedClick.bind(this)} />
                            </div>
                        </div>
                }
            </Dialogue>
        );
    }
}
