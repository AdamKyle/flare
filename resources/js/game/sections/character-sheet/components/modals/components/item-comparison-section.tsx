import React, {Fragment} from "react";
import InventoryComparisonAdjustment
    from "../../../../../lib/game/character-sheet/types/modal/inventory-comparison-adjustment";
import {capitalize} from "lodash";
import clsx from "clsx";
import Tabs from "../../../../../components/ui/tabs/tabs";
import TabPanel from "../../../../../components/ui/tabs/tab-panel";
import {formatNumber} from "../../../../../lib/game/format-number";
import ItemNameColorationText from "../../../../../components/ui/item-name-coloration-text";
import ItemsAttachedSkills from "../../../../../lib/game/character-sheet/types/modal/items-attached-skills";

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

    renderWholeNumber(details: InventoryComparisonAdjustment, key: string) {
        const wholeNumberValues = ['damage_adjustment', 'damage', 'ac_adjustment', 'healing_adjustment', 'base_damage', 'base_healing', 'base_ac', 'holy_stacks', 'holy_stacks_applied'];

        if (wholeNumberValues.includes(key)) {
            return this.formatWholeNumber(details[key]);
        }

        return this.renderPercent(details[key]);
    }

    renderName(key: string): string {
        if (key === 'damage') {
            return 'Affix Damage';
        }

        if (key === 'holy_stack_devouring_darkness') {
            return 'Holy Devouring Light/Dark Resistance'
        }

        if (key.includes('_reduction')) {
            return 'Enemy ' + key.split('_').join(' ');
        }

        return capitalize(key.split('_').join(' '));
    }

    renderChange(details: InventoryComparisonAdjustment, itemToEquip?: InventoryComparisonAdjustment) {
        const invalidFields     = ['id', 'min_cost', 'skill_level_req', 'skill_level_trivial', 'holy_level', 'holy_stacks', 'holy_stacks_applied', 'reduces_enemy_stats', 'cost', 'shop_cost', 'slot_id', 'affix_count', 'is_unique'];

        let elements = Object.keys(details).map((key) => {
            if (!invalidFields.includes(key)) {
                if (typeof details[key] === 'number' && details[key] !== 0) {
                    return (
                        <Fragment>
                            <dt>{this.renderName(key)}</dt>
                            <dd className={clsx(
                                {
                                    'text-green-600 dark:text-green-500': details[key] > 0
                                },
                                {
                                    'text-red-600 dark:text-red-400': details[key] < 0
                                }
                            )}>{
                                this.renderWholeNumber(details, key)
                            }</dd>
                        </Fragment>
                    );
                }
            }
        }).filter((e: any) => typeof e !== 'undefined');

        if (elements.length === 0 && typeof itemToEquip !== 'undefined') {
            return (
                <Fragment>
                    <p className='mb-4'>This item is identical to the one you have equipped.</p>
                    <dl>
                        <dt>Position: </dt>
                        <dd className={'text-blue-600 dark:text-blue-500'}>{this.formatPosition(details['position'])}</dd>
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

        const skills = details.skills.map((skill: ItemsAttachedSkills) => {
            return (
                <Fragment>
                    <dt>Affects Skill:</dt>
                    <dd>{skill.skill_name}</dd>
                    <dt>XP Bonus:</dt>
                    <dd className={clsx(
                        {
                            'text-green-600 dark:text-green-500': skill.skill_training_bonus > 0
                        },
                        {
                            'text-red-600 dark:text-red-400': skill.skill_training_bonus < 0
                        }
                    )}>{this.renderPercent(skill.skill_training_bonus)}</dd>
                    <dt>Skill Bonus:</dt>
                    <dd className={clsx(
                        {
                            'text-green-600 dark:text-green-500': skill.skill_bonus > 0
                        },
                        {
                            'text-red-600 dark:text-red-400': skill.skill_bonus < 0
                        }
                    )}>{this.renderPercent(skill.skill_bonus)}</dd>
                </Fragment>
            )
        });

        elements = [...elements, ...skills];

        return (
            <Fragment>
                <dl>
                    {elements}
                </dl>
            </Fragment>
        )
    }

    renderItemToEquip(itemToEquip: InventoryComparisonAdjustment) {
        const invalidFields     = ['id', 'min_cost', 'skill_level_req', 'skill_level_trivial', 'reduces_enemy_stats', 'cost', 'shop_cost', 'slot_id', 'holy_level', 'affix_count', 'is_unique'];
        const wholeNumberValues = ['damage_adjustment', 'damage', 'ac_adjustment', 'healing_adjustment', 'base_damage', 'base_ac', 'holy_stacks', 'holy_stacks_applied', 'base_healing'];

        let elements = Object.keys(itemToEquip).map((key) => {
            if (!invalidFields.includes(key)) {
                if (itemToEquip[key] > 0) {
                    return (
                        <Fragment>
                            <dt>{this.renderName(key)}</dt>
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

        const skills = itemToEquip.skills.map((skill: ItemsAttachedSkills) => {
            return (
                <Fragment>
                    <dt>Affects Skill:</dt>
                    <dd>{skill.skill_name}</dd>
                    <dt>XP Bonus:</dt>
                    <dd className={clsx(
                        {
                            'text-green-600 dark:text-green-500': skill.skill_training_bonus > 0
                        },
                        {
                            'text-red-600 dark:text-red-400': skill.skill_training_bonus < 0
                        }
                    )}>{this.renderPercent(skill.skill_training_bonus)}</dd>
                    <dt>Skill Bonus:</dt>
                    <dd className={clsx(
                        {
                            'text-green-600 dark:text-green-500': skill.skill_bonus > 0
                        },
                        {
                            'text-red-600 dark:text-red-400': skill.skill_bonus < 0
                        }
                    )}>{this.renderPercent(skill.skill_bonus)}</dd>
                </Fragment>
            )
        });

        return [...elements, ...skills];
    }

    renderTwoComparisons() {
        if (this.props.comparison_details !== null) {
            return (
                <div className='grid w-full lg:grid-cols-2 md:m-auto max-h-[400px] lg:max-h-[500px] overflow-y-auto'>
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
                <div className='max-h-[400px] lg:max-h-[500px] overflow-y-auto'>
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
            <div className='max-h-[200px] md:max-h-[400px] lg:max-h-[500px] overflow-y-auto'>
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

    formatPosition(position: string|number) {
        if (typeof position === 'string') {
            return capitalize(position.split('-').join(' '));
        }

        return position;
    }

    formatWholeNumber(value: string|number) {
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
                    this.props.comparison_details.details.length > 0 ?
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
