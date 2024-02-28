import React from "react";
import clsx from "clsx";
import {formatNumber} from "../../lib/game/format-number";
import ItemNameColorationText from "../../sections/items/item-name/item-name-coloration-text";
import {ItemType} from "../../sections/items/enums/item-type";

const coreAttributes = [
    'str', 'dex', 'dur', 'int', 'chr', 'agi', 'focus'
];

export default class ExpandedComparison extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    isValueBelowZero(value: number): boolean {
        return value < 0;
    }

    isValueAboveZero(value: number): boolean {
        return value > 0;
    }

    mapCoreAttributes(attributeName: string) {
        switch(attributeName) {
            case 'str':
                return 'Strength';
            case 'dex':
                return 'Dexterity';
            case 'dur':
                return 'Durability';
            case 'int':
                return'Intelligence';
            case 'chr':
                return 'Charisma';
            case 'agi':
                return 'Agility';
            case 'focus':
                return'Focus';
            default:
                return 'ERROR';
        }
    }

    renderCoreAttributes() {
        return coreAttributes.map((attribute: string) => {
            return (
                <>
                    <dt>{this.mapCoreAttributes(attribute)}</dt>
                    <dd className={clsx({
                        'text-green-700 dark:text-green-500' : this.isValueAboveZero(this.props.comparison_details[attribute + '_adjustment']),
                        'text-red-700 dark:text-red-500' : this.isValueBelowZero(this.props.comparison_details[attribute + '_adjustment']),
                        'text-gray-700 dark:text-white': this.props.comparison_details[attribute + '_adjustment'] === 0,
                    })}>
                        {(this.props.comparison_details[attribute + '_adjustment'] * 100).toFixed(2) + '%'}
                    </dd>
                </>
            )
        })
    }

    renderEnemyStatReductions() {
        return coreAttributes.map((attribute: string) => {
            return (
                <>
                    <dt>{this.mapCoreAttributes(attribute)}</dt>
                    <dd className={clsx({
                        'text-green-700 dark:text-green-500' : this.isValueAboveZero(this.props.comparison_details[attribute + '_reduction']),
                        'text-red-700 dark:text-red-500' : this.isValueBelowZero(this.props.comparison_details[attribute + '_reduction']),
                        'text-gray-700 dark:text-white': this.props.comparison_details[attribute + '_reduction'] === 0,
                    })}>
                        {(this.props.comparison_details[attribute + '_reduction'] * 100).toFixed(2) + '%'}
                    </dd>
                </>
            )
        })
    }

    renderAttackChange() {
        return (
            <dl>
                <dt>Attack</dt>
                <dd className={clsx({
                    'text-green-700 dark:text-green-500' : this.isValueAboveZero(this.props.comparison_details.damage_adjustment),
                    'text-red-700 dark:text-red-500' : this.isValueBelowZero(this.props.comparison_details.damage_adjustment),
                    'text-gray-700 dark:text-white': this.props.comparison_details.damage_adjustment === 0,
                })}>
                    {formatNumber(this.props.comparison_details.damage_adjustment)}
                </dd>
                <dt>Base Damage Modifier</dt>
                <dd className={clsx({
                    'text-green-700 dark:text-green-500' : this.isValueAboveZero(this.props.comparison_details.base_damage_mod_adjustment),
                    'text-red-700 dark:text-red-500' : this.isValueBelowZero(this.props.comparison_details.base_damage_mod_adjustment),
                    'text-gray-700 dark:text-white': this.props.comparison_details.base_damage_mod_adjustment === 0,
                })}>
                    {(this.props.comparison_details.base_damage_mod_adjustment * 100).toFixed(2)}%
                </dd>
                {this.renderSpellDetails()}
            </dl>
        )
    }

    renderSpellDetails() {
        const validTypes = [
            ItemType.SPELL_DAMAGE,
            ItemType.RING,
        ];

        if (!validTypes.includes(this.props.comparison_details.type)) {
            return;
        }

        return (
            <>
                <dt>Spell Evasion</dt>
                <dd className={clsx({
                    'text-green-700 dark:text-green-500' : this.isValueAboveZero(this.props.comparison_details.spell_evasion_adjustment),
                    'text-red-700 dark:text-red-500' : this.isValueBelowZero(this.props.comparison_details.spell_evasion_adjustment),
                    'text-gray-700 dark:text-white': this.props.comparison_details.spell_evasion_adjustment === 0,
                })}>
                    {(this.props.comparison_details.spell_evasion_adjustment * 100).toFixed(2)}%
                </dd>
            </>
        )
    }

    renderDefenceChange() {
        return (
            <dl>
                <dt>Defence</dt>
                <dd className={clsx({
                    'text-green-700 dark:text-green-500' : this.isValueAboveZero(this.props.comparison_details.ac_adjustment),
                    'text-red-700 dark:text-red-500' : this.isValueBelowZero(this.props.comparison_details.ac_adjustment),
                    'text-gray-700 dark:text-white': this.props.comparison_details.ac_adjustment === 0,
                })}>
                    {formatNumber(this.props.comparison_details.ac_adjustment)}
                </dd>
                <dt>Base AC Modifier</dt>
                <dd className={clsx({
                    'text-green-700 dark:text-green-500' : this.isValueAboveZero(this.props.comparison_details.base_ac_adjustment),
                    'text-red-700 dark:text-red-500' : this.isValueBelowZero(this.props.comparison_details.base_ac_adjustment),
                    'text-gray-700 dark:text-white': this.props.comparison_details.base_ac_adjustment === 0,
                })}>
                    {(this.props.comparison_details.base_ac_adjustment * 100).toFixed(2)}%
                </dd>
            </dl>
        )
    }

    renderAttackOrDefenceAdjustment() {
        const damageBased = [
            ItemType.WEAPON,
            ItemType.MACE,
            ItemType.STAVE,
            ItemType.FAN,
            ItemType.HAMMER,
            ItemType.GUN,
            ItemType.SPELL_DAMAGE,
            ItemType.SCRATCH_AWL,
            ItemType.RING
        ];

        if (damageBased.includes(this.props.comparison_details.type)) {
            return this.renderAttackChange();
        }

        return this.renderDefenceChange();
    }

    renderSkillsChanges() {
        if (this.props.comparison_details.skills.lengh === 0) {
            return;
        }

        if (this.props.comparison_details.skills.length > 1) {
            return (
                <div>
                    <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4'></div>
                    <strong>Effected Skill Adjustments</strong>
                    <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2'></div>
                    <div className='grid md:grid-cols-2 gap-2'>
                        {this.renderSkillChange(true)}
                    </div>
                </div>
            );
        }

        return(
            <div>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4'></div>
                <strong>Effected Skill Adjustments</strong>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2'></div>
                <div className='grid md:grid-cols-2 gap-2'>
                    {this.renderSkillChange(true)}
                </div>
            </div>
        );
    }

    renderSkillChange(showSeparator: boolean) {

        let hasShown = false;

        return this.props.comparison_details.skills.map((skill: any) => {

            const element = (
                <>
                    <div>
                        <dl>
                            <dt>Skill Name</dt>
                            <dd>{skill.skill_name}</dd>
                            <dt>Skill Bonus Adj.</dt>
                            <dd className={clsx({
                                'text-green-700 dark:text-green-500' : this.isValueAboveZero(skill.skill_bonus),
                                'text-red-700 dark:text-red-500' : this.isValueBelowZero(skill.skill_bonus),
                                'text-gray-700 dark:text-white': skill.skill_bonus === 0,
                            })}>
                                {(skill.skill_bonus * 100).toFixed(2)}%
                            </dd>
                            <dt>Skill Xp Bonus Adj.</dt>
                            <dd className={clsx({
                                'text-green-700 dark:text-green-500' : this.isValueAboveZero(skill.skill_training_bonus),
                                'text-red-700 dark:text-red-500' : this.isValueBelowZero(skill.skill_training_bonus),
                                'text-gray-700 dark:text-white': skill.skill_training_bonus === 0,
                            })}>
                                {(skill.skill_training_bonus * 100).toFixed(2)}%
                            </dd>
                        </dl>
                    </div>
                    {
                        showSeparator && !hasShown ?
                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4 sm:block md:hidden'></div>
                        : null
                    }
                </>
            );

            if (showSeparator) {
                hasShown = true;
            }

            return element;
        })
    }

    render() {
        return (
            <div>
                <h2 className='my-4'>
                    <ItemNameColorationText item={this.props.comparison_details} custom_width={false} />
                </h2>
                <div className='my-4'>
                    {this.renderAttackOrDefenceAdjustment()}
                </div>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2'></div>
                <div className='grid md:grid-cols-2 gap-2'>
                    <div>
                        <strong>Stat Adjustment</strong>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2'></div>
                        <dl>
                            {this.renderCoreAttributes()}
                        </dl>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2'></div>
                        <strong>Enemy Stat Reductions</strong>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2'></div>
                        <dl>
                            {this.renderEnemyStatReductions()}
                        </dl>
                    </div>
                    <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4 sm:block md:hidden'></div>
                    <div>
                        <strong>Counter & Ambush</strong>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2'></div>
                        <dl>
                            <dt>Counter Chance</dt>
                            <dd className={clsx({
                                'text-green-700 dark:text-green-500' : this.isValueAboveZero(this.props.comparison_details.counter_chance_adjustment),
                                'text-red-700 dark:text-red-500' : this.isValueBelowZero(this.props.comparison_details.counter_chance_adjustment),
                                'text-gray-700 dark:text-white': this.props.comparison_details.counter_chance_adjustment === 0,
                            })}>
                                {(this.props.comparison_details.counter_chance_adjustment * 100).toFixed(2)}%
                            </dd>
                            <dt>Counter Resistance Chance</dt>
                            <dd className={clsx({
                                'text-green-700 dark:text-green-500' : this.isValueAboveZero(this.props.comparison_details.counter_resistance_adjustment),
                                'text-red-700 dark:text-red-500' : this.isValueBelowZero(this.props.comparison_details.counter_resistance_adjustment),
                                'text-gray-700 dark:text-white': this.props.comparison_details.counter_resistance_adjustment === 0,
                            })}>
                                {(this.props.comparison_details.counter_resistance_adjustment * 100).toFixed(2)}%
                            </dd>
                            <dt>Ambush Chance</dt>
                            <dd className={clsx({
                                'text-green-700 dark:text-green-500' : this.isValueAboveZero(this.props.comparison_details.ambush_chance_adjustment),
                                'text-red-700 dark:text-red-500' : this.isValueBelowZero(this.props.comparison_details.ambush_chance_adjustment),
                                'text-gray-700 dark:text-white': this.props.comparison_details.ambush_chance_adjustment === 0,
                            })}>
                                {(this.props.comparison_details.ambush_chance_adjustment * 100).toFixed(2)}%
                            </dd>
                            <dt>Ambush Resistance</dt>
                            <dd className={clsx({
                                'text-green-700 dark:text-green-500' : this.isValueAboveZero(this.props.comparison_details.ambush_resistance_adjustment),
                                'text-red-700 dark:text-red-500' : this.isValueBelowZero(this.props.comparison_details.ambush_resistance_adjustment),
                                'text-gray-700 dark:text-white': this.props.comparison_details.ambush_resistance_adjustment === 0,
                            })}>
                                {(this.props.comparison_details.ambush_resistance_adjustment * 100).toFixed(2)}%
                            </dd>
                        </dl>
                        {this.renderSkillsChanges()}
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4'></div>
                        <strong>Misc. Modifiers</strong>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2'></div>
                        <dl>
                            <dt>Entrancing Chance</dt>
                            <dd className={clsx({
                                'text-green-700 dark:text-green-500' : this.isValueAboveZero(this.props.comparison_details.entranced_chance),
                                'text-red-700 dark:text-red-500' : this.isValueBelowZero(this.props.comparison_details.entranced_chance),
                                'text-gray-700 dark:text-white': this.props.comparison_details.entranced_chance === 0,
                            })}>
                                {(this.props.comparison_details.entranced_chance * 100).toFixed(2)}%
                            </dd>
                            <dt>Steal Life Chance</dt>
                            <dd className={clsx({
                                'text-green-700 dark:text-green-500' : this.isValueAboveZero(this.props.comparison_details.steal_life_amount),
                                'text-red-700 dark:text-red-500' : this.isValueBelowZero(this.props.comparison_details.steal_life_amount),
                                'text-gray-700 dark:text-white': this.props.comparison_details.steal_life_amount === 0,
                            })}>
                                {(this.props.comparison_details.steal_life_amount * 100).toFixed(2)}%
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        )
    }
}
