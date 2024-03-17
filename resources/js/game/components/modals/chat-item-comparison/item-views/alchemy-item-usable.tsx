import React, {ReactNode} from "react";
import ItemViewProps from "../types/item-view-props";
import clsx from "clsx";
import {capitalize, startCase} from "lodash";

const statKeys = [
    'str_mod', 'dex_mod', 'dur_mod', 'int_mod', 'chr_mod', 'agi_mod', 'focus_mod'
];

const baseModifierKeys = [
    'base_damage_mod', 'base_ac_mod', 'base_healing_mod',
];

const baseSkillModifierKeys = [
    'base_damage_mod_bonus', 'base_ac_mod_bonus', 'base_healing_mod_bonus',
];

const miscSkillModifiers = [
    'increase_skill_bonus_by',
    'increase_skill_training_bonus_by',
    'fight_time_out_mod_bonus',
    'move_time_out_mod_bonus',
]

export default class AlchemyItemUsable extends React.Component<ItemViewProps, {}> {

    constructor(props: ItemViewProps) {
        super(props);
    }

    shouldRenderColumns(): boolean {
        const validStats = statKeys.filter((key: string) => {
            return this.props.item[key] > 0;
        });

        const validModifiers = baseModifierKeys.filter((key: string) => {
            return this.props.item[key] > 0;
        });

        return validStats.length > 0 && validModifiers.length > 0;
    }

    shouldRenderBaseSkillModifiers() {
        return baseSkillModifierKeys.filter((key: string) => {
            return this.props.item[key] > 0
        }).length > 0;
    }

    shouldRenderSkillModifiers() {
        return miscSkillModifiers.filter((key: string) => {
            return this.props.item[key] > 0
        }).length > 0;
    }

    renderKingdomDamage(): ReactNode {
        return (
            <>
                <p className='mt-4 mb-4 text-sky-700 dark:text-sky-600'>
                    This item can only be used on kingdoms. The damage it does can stack if you drop multiple items.
                    The damage can be reduced by the kingdoms over all defence bonuses.
                </p>
                <p className='mb-4'>
                    When you use this item on a kingdom you will do the % of damage listed below for
                    one item to all aspects of the kingdom.
                </p>
                <dl>
                    <dt>Damage to kingdom</dt>
                    <dd className={clsx({
                        'text-green-700 dark:text-green-500': this.props.item.kingdom_damage > 0
                    })}>{(this.props.item.kingdom_damage * 100).toFixed(2)}%
                    </dd>
                </dl>
            </>
        )
    }

    renderStatSection(): ReactNode {
        const validStats = statKeys.filter((key: string) => {
            return this.props.item[key] > 0;
        });

        const statList = validStats.map((stat: string) => {
            return (
                <>
                    <dt>{startCase(stat).replace(/\s/g, ' ')}</dt>
                    <dd className={'text-green-700 dark:text-green-500'}>
                        +{(this.props.item[stat] * 100).toFixed(2)}%
                    </dd>
                </>
            )
        });

        if (statList.length <= 0) {
            return;
        }

        return(
            <dl>
                {statList}
            </dl>
        )
    }

    renderBaseModifiersSection(): ReactNode {
        const validModifiers = baseModifierKeys.filter((key: string) => {
            return this.props.item[key] > 0;
        });

        const modifiersList = validModifiers.map((baseModifier: string) => {
            return (
                <>
                    <dt>{startCase(baseModifier).replace(/\s/g, ' ')}</dt>
                    <dd className={'text-green-700 dark:text-green-500'}>
                        +{(this.props.item[baseModifier] * 100).toFixed(2)}%
                    </dd>
                </>
            )
        });

        if (modifiersList.length <= 0) {
            return;
        }

        return(
            <>
                <dl>
                    {modifiersList}
                </dl>
                {
                    this.shouldRenderBaseSkillModifiers() ?
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    : null
                }
            </>
        );
    }

    renderBaseSkillModifiers(): ReactNode {
        const validModifiers = baseSkillModifierKeys.filter((key: string) => {
            return this.props.item[key] > 0;
        });

        const modifiersList = validModifiers.map((baseSkillModifier: string) => {
            return (
                <>
                    <dt>{startCase(baseSkillModifier).replace(/\s/g, ' ')}</dt>
                    <dd className={'text-green-700 dark:text-green-500'}>
                        +{(this.props.item[baseSkillModifier] * 100).toFixed(2)}%
                    </dd>
                </>
            )
        })

        if (modifiersList.length <= 0) {
            return;
        }

        return(
            <>
                <p className={'my-4'}>
                    These modifiers apply to your class skill, which can be seen on your character sheet, under skills. The skill
                    will be orange.
                </p>
                <dl>
                    {modifiersList}
                </dl>
            </>
        );
    }

    renderSkillSection() {
        const validModifiers = miscSkillModifiers.filter((key: string) => {
            return this.props.item[key] > 0;
        });

        const modifiersList = validModifiers.map((miscSkillModifier: string) => {
            return (
                <>
                    <dt>{startCase(miscSkillModifier).replace(/\s/g, ' ')}</dt>
                    <dd className={'text-green-700 dark:text-green-500'}>
                        +{(this.props.item[miscSkillModifier] * 100).toFixed(2)}%
                    </dd>
                </>
            )
        })

        if (modifiersList.length <= 0) {
            return;
        }

        return(
            <>
                <p className={'my-4'}>
                    These modifiers will apply to the following skills: <strong>{this.props.item.skills.join(', ')}</strong>
                </p>
                <dl>
                    {modifiersList}
                </dl>
            </>
        );
    }

    renderUsableColumns(): ReactNode {
        return (
            <div className='grid grid-cols-2 gap-2'>
                <div>
                    {
                        this.props.item.stat_increase ?
                            <dl>
                                <dt>All Stat increase %</dt>
                                <dd className='text-green-700 dark:text-green-600'>{(this.props.item.stat_increase * 100).toFixed(2)}%</dd>
                            </dl>
                            :
                            this.renderStatSection()
                    }
                </div>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3 block md:hidden'></div>
                <div>
                    {this.renderBaseModifiersSection()}
                    {
                        this.shouldRenderBaseSkillModifiers() ?
                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            : null
                    }
                    {this.renderBaseSkillModifiers()}
                </div>
                {
                    this.shouldRenderSkillModifiers() ?
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                        : null
                }
                {this.renderSkillSection()}
            </div>
        )
    }

    renderSingleColumnDetails(): ReactNode {
        return (
            <>
                {
                    this.props.item.stat_increase ?
                        <dl>
                            <dt>All Stat increase %</dt>
                            <dd className='text-green-700 dark:text-green-600'>{(this.props.item.stat_increase * 100).toFixed(2)}%</dd>
                        </dl>
                        :
                        this.renderStatSection()
                }
                {this.renderBaseModifiersSection()}
                {this.renderBaseSkillModifiers()}
                {this.renderSkillSection()}
            </>
        )
    }


    renderUsableSection() {

        let lastsFor = this.props.item.lasts_for;
        let lastForLabel = 'minutes';

        if (lastsFor > 60) {
            lastsFor = lastsFor / 60;
            lastForLabel = 'hours';
        }

        return (
            <>
                <p className='mb-4 text-sky-700 dark:text-sky-500'>
                    <strong>Lasts For: </strong> {lastsFor} {lastForLabel}.
                </p>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <dl>
                    <dt>Can Stack?</dt>
                    <dd>{this.props.item.can_stack ? 'Yes' : 'No'}</dd>
                    <dt>Grants additional level upon level up?</dt>
                    <dd>{this.props.item.gain_additional_level ? 'Yes' : 'No'}</dd>
                    <dt>XP Bonus per Kill (%)</dt>
                    <dd>{(this.props.item.xp_bonus * 100).toFixed(2)}%</dd>
                </dl>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                {
                    this.shouldRenderColumns() ?
                        this.renderUsableColumns()
                    :
                        this.renderSingleColumnDetails()
                }
            </>
        )
    }

    renderCoreView(): ReactNode {
        if (this.props.item.damages_kingdoms) {
            return this.renderKingdomDamage();
        }

        return this.renderUsableSection();
    }

    render() {
        return (
            <div className='mr-auto ml-auto w-3/5'>
                <p className='mt-4 mb-4'>
                    {this.props.item.description}
                </p>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                {this.renderCoreView()}
            </div>
        )
    }
}
