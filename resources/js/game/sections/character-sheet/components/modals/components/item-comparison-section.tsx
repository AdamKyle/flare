import React, {Fragment} from "react";
import InventoryComparisonAdjustment
    from "../../../../../lib/game/character-sheet/types/modal/inventory-comparison-adjustment";
import {capitalize} from "lodash";
import clsx from "clsx";
import Tabs from "../../../../../components/ui/tabs/tabs";
import TabPanel from "../../../../../components/ui/tabs/tab-panel";
import {formatNumber} from "../../../../../lib/game/format-number";
import ItemNameColorationText from "../../../../../components/ui/item-name-coloration-text";

export default class ItemComparisonSection extends React.Component<any, any> {

    private tabs: {name: string, key: string}[];

    constructor(props: any) {
        super(props);

        this.tabs = [{
            key: 'general',
            name: 'General'
        }, {
            key: 'comparison',
            name: 'Comparison',
        }]
    }

    renderChange(details: InventoryComparisonAdjustment, itemToEquip?: InventoryComparisonAdjustment) {
        const invalidFields     = ['id', 'min_cost', 'skill_level_req', 'skill_level_trivial', 'holy_level', 'holy_stack_devouring_darkness', 'holy_stack_stat_bonus', 'holy_stacks', 'holy_stacks_applied', 'cost', 'slot_id', 'affix_count'];
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
        const invalidFields     = ['id', 'min_cost', 'skill_level_req', 'skill_level_trivial', 'cost', 'slot_id', 'holy_level', 'affix_count'];
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
        if (this.props.comparison_details !== null) {
            return (
                <div className='grid w-full md:grid-cols-2 md:m-auto'>
                    <div>
                        <div className={'font-light pb-3'}>
                            <ItemNameColorationText item={this.props.comparison_details.details[0]} />
                        </div>
                        {this.renderChange(this.props.comparison_details.details[0], this.props.comparison_details.itemToEquip)}
                    </div>
                    <div
                        className='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-3 mt-6'></div>
                    <div>
                        <div className={'font-light mb-3'}>
                            <ItemNameColorationText item={this.props.comparison_details.details[1]} />
                        </div>
                        {this.renderChange(this.props.comparison_details.details[1], this.props.comparison_details.itemToEquip)}
                    </div>
                </div>
            );
        }

        return null;
    }

    renderSingleComparison() {
        if (this.props.comparison_details !== null) {
            return (
                <div>
                    <div className={'font-light pb-3'}>
                        <ItemNameColorationText item={this.props.comparison_details.details[0]} />
                    </div>
                    {this.renderChange(this.props.comparison_details.details[0], this.props.comparison_details.itemToEquip)}
                </div>
            );
        }

        return null;
    }

    renderSingleItem() {
        if (this.props.comparison_details === null) {
            return null;
        }

        return (
            <div>
                <dl>
                    {this.renderItemToEquip(this.props.comparison_details.itemToEquip)}
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

    render() {
        return (
            <Fragment>
                {
                    this.props.comparison_details.details.length > 0 && this.props.view_port >= 1600 ?
                        this.props.comparison_details.details.length === 2 ?
                            this.renderTabs(true)
                            :
                            this.renderTabs(false)
                        :
                        this.renderSingleItem()
                }
            </Fragment>
        )
    }
}
