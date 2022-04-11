import React, {Fragment} from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
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
import {formatNumber} from "../../../../lib/game/format-number";
import EquipModal from "./components/inventory-comparison/equip-modal";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import InventoryItemComparisonProps
    from "../../../../lib/game/character-sheet/types/modal/inventory-item-comparison-props";
import Tabs from '../../../../components/ui/tabs/tabs';
import TabPanel from "../../../../components/ui/tabs/tab-panel";

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
            comparison_details: null,
            show_equip_modal: false,
            is_equipping: false,
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
            is_equipping: true
        });

        (new Ajax()).setRoute('character/'+this.props.character_id+'/inventory/equip-item').setParameters({
            position: position,
            slot_id: this.props.slot_id,
            equip_type: type,
        }).doAjaxCall('post', (result: AxiosResponse) => {
            this.setState({
                is_equipping: false,
            }, () => {
                this.props.update_inventory(result.data.inventory);

                this.props.manage_modal();
            })
        }, (error: AxiosError) => {

        });
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
        const invalidFields     = ['id', 'min_cost', 'skill_level_req', 'skill_level_trivial', 'holy_level', 'holy_stack_devouring_darkness', 'holy_stack_stat_bonus', 'holy_stacks', 'holy_stacks_applied', 'cost', 'slot_id'];
        const wholeNumberValues = ['damage_adjustment', 'ac_adjustment', 'healing_adjustment', 'base_damage'];

        let elements = Object.keys(details).map((key) => {
            if (!invalidFields.includes(key)) {
                if (typeof details[key] === 'number' && details[key] !== 0) {
                    return (
                        <Fragment>
                            <dt>{capitalize(key.split('_').join(' '))}</dt>
                            <dd className={clsx(
                                {
                                    'text-green-600 dark:text-green-500': details[key] > 0
                                },
                                {
                                    'text-red-600 dark:text-red-400': details[key] < 0
                                }
                            )}>{
                                wholeNumberValues.includes(key) ?
                                    this.formatWholeNumber(details[key])
                                :
                                    this.renderPercent(details[key])
                            }</dd>
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

        elements.unshift(
            <Fragment>
                <dt>Position: </dt>
                <dd className={'text-blue-600 dark:text-blue-500'}>{this.formatPosition(details['position'])}</dd>
            </Fragment>
        );

        return (
            <Fragment>
                <dl>
                    {elements}
                </dl>
            </Fragment>
        )
    }

    renderItemToEquip(itemToEquip: InventoryComparisonAdjustment) {
        const invalidFields     = ['id', 'min_cost', 'skill_level_req', 'skill_level_trivial', 'cost', 'slot_id', 'holy_level'];
        const wholeNumberValues = ['damage_adjustment', 'ac_adjustment', 'healing_adjustment', 'base_damage', 'holy_stacks', 'holy_stacks_applied'];

        return Object.keys(itemToEquip).map((key) => {
            if (!invalidFields.includes(key)) {
                if (itemToEquip[key] > 0) {
                    return (
                        <Fragment>
                            <dt>{capitalize(key.split('_').join(' '))}</dt>
                            <dd className={clsx(
                                {
                                    'text-green-600 dark:text-green-500': itemToEquip[key] > 0
                                },
                                {
                                    'text-red-600 dark:text-red-400': itemToEquip[key] < 0
                                }
                            )}>{
                                wholeNumberValues.includes(key) ?
                                    this.formatWholeNumber(itemToEquip[key])
                                    :
                                    this.renderPercent(itemToEquip[key])
                            }</dd>
                        </Fragment>
                    );
                }
            }
        });
    }

    renderTwoComparisons() {
        if (this.state.comparison_details !== null) {
            return (
                <div className='grid w-full md:grid-cols-2 md:m-auto'>
                    <div>
                        <div className={'font-light pb-3'}>{this.state.comparison_details.details[0].name}</div>
                        {this.renderChange(this.state.comparison_details.details[0], this.state.comparison_details.itemToEquip)}
                    </div>
                    <div
                        className='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-3 mt-6'></div>
                    <div>
                        <div className={'font-light mb-3'}>{this.state.comparison_details.details[1].name}</div>
                        {this.renderChange(this.state.comparison_details.details[1], this.state.comparison_details.itemToEquip)}
                    </div>
                </div>
            );
        }

        return null;
    }

    renderSingleComparison() {
        if (this.state.comparison_details !== null) {
            return (
                <div>
                    <div className={'font-light pb-3'}>{this.state.comparison_details.details[0].name}</div>
                    {this.renderChange(this.state.comparison_details.details[0], this.state.comparison_details.itemToEquip)}
                </div>
            );
        }

        return null;
    }

    renderSingleItem() {
        if (this.state.comparison_details === null) {
            return null;
        }

        return (
            <div>
                <dl>
                    {this.renderItemToEquip(this.state.comparison_details.itemToEquip)}
                </dl>
            </div>
        )
    }

    renderTabs(double: boolean) {
        return (
            <Tabs tabs={this.tabs}>
                <TabPanel key={'general'}>
                    <div className='mb-10'>
                        {this.renderSingleItem()}
                    </div>
                </TabPanel>
                <TabPanel key={'comparison'}>
                    {
                        double ?
                            this.renderTwoComparisons()
                        :
                            this.renderSingleComparison()
                    }
                </TabPanel>
            </Tabs>
        )
    }

    formatPosition(position: string|number|boolean) {
        if (typeof position === 'string') {
            return capitalize(position.split('-').join(' '));
        }

        return position;
    }

    formatWholeNumber(value: string|number|boolean) {
        if (typeof value === 'number') {
            return formatNumber(value);
        }

        return value;
    }

    renderPercent(value: any) {
        return (value * 100).toFixed(2) + '%'
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

    stubbedClick(){}

    render() {

        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={this.buildTitle()}
                      secondary_actions={null}
                      large_modal={this.isLargeModal()}
                      primary_button_disabled={this.state.is_equipping}
            >
                {
                    this.state.loading || this.state.comparison_details === null ?
                        <div className='p-5 mb-2'><ComponentLoading /></div>
                    :
                        <div className='p-5'>
                            {
                                this.state.comparison_details.details.length > 0 ?
                                    this.state.comparison_details.details.length === 2 ?
                                        this.renderTabs(true)
                                    :
                                        this.renderTabs(false)
                                :
                                    this.renderSingleItem()
                            }
                            <div className='border-b-2 mt-6 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <div className={clsx(
                                'mt-6 grid grid-cols-1 w-full gap-2 md:m-auto',
                                {
                                    'md:w-3/4': this.isLargeModal()
                                }, {
                                    'md:grid-cols-6': this.state.comparison_details.itemToEquip.affix_count > 0 && this.state.comparison_details.itemToEquip.holy_stacks_applied > 0,
                                    'md:grid-cols-5': (this.state.comparison_details.itemToEquip.affix_count > 0 && this.state.comparison_details.itemToEquip.holy_stacks_applied === 0) || (this.state.comparison_details.itemToEquip.affix_count === 0 && this.state.comparison_details.itemToEquip.holy_stacks_applied > 0),
                                    'md:grid-cols-4': this.state.comparison_details.itemToEquip.affix_count === 0 && this.state.comparison_details.itemToEquip.holy_stacks_applied === 0,
                                }
                            )}>
                                <PrimaryOutlineButton button_label={'Equip'} on_click={this.manageEquipModal.bind(this)} disabled={this.state.is_equipping}/>
                                <PrimaryOutlineButton button_label={'Move'} on_click={this.stubbedClick.bind(this)} disabled={this.state.is_equipping}/>
                                <SuccessOutlineButton button_label={'Sell'} on_click={this.stubbedClick.bind(this)} disabled={this.state.is_equipping}/>

                                {
                                    this.state.comparison_details.itemToEquip.affix_count > 0 || this.state.comparison_details.itemToEquip.holy_stacks_applied > 0 ?
                                        <SuccessOutlineButton button_label={'List'}
                                                              on_click={this.stubbedClick.bind(this)}
                                                              disabled={this.state.is_equipping}/>
                                        : null
                                }

                                {
                                    this.state.comparison_details.itemToEquip.affix_count > 0 ?
                                        <DangerOutlineButton button_label={'Disenchant'} on_click={this.stubbedClick.bind(this)} disabled={this.state.is_equipping}/>
                                    : null
                                }

                                <DangerOutlineButton button_label={'Destroy'} on_click={this.stubbedClick.bind(this)} disabled={this.state.is_equipping}/>
                            </div>

                            {
                                this.state.is_equipping ?
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
                        </div>
                }
            </Dialogue>
        );
    }
}
