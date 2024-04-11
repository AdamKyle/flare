import React, {ReactNode} from "react";
import {isWeaponType} from "./helpers/is-weapon-type";
import {formatNumber} from "../../lib/game/format-number";
import {isArmourType} from "./helpers/is-armour-type";
import {startCase} from "lodash";
import ItemDefinition from "./deffinitions/item-definition";
import {ItemType} from "./enums/item-type";
import ItemToEquip from "../item-comparison/deffinitions/item-to-equip";
import clsx from "clsx";

interface ItemProps {
    item: ItemDefinition | ItemToEquip;
}

const coreAttributes = [
    'str', 'dex', 'dur', 'int', 'chr', 'agi', 'focus'
];

export default class Item extends React.Component<ItemProps, any> {
    constructor(props: ItemProps) {
        super(props);
    }

    getName() {
        return <span className='text-gray-600 dark:text-white'>
            {this.props.item.name}
        </span>
    }

    renderAttackChange() {
        return (
            <dl>
                <dt>Attack</dt>
                <dd className={clsx({
                    'text-green-700 dark:text-green-500' : this.isValueAboveZero(this.props.item.damage_adjustment),
                    'text-red-700 dark:text-red-500' : this.isValueBeloZero(this.props.item.damage_adjustment),
                    'text-gray-700 dark:text-white': this.props.item.damage_adjustment === 0,
                })}>
                    {formatNumber(this.props.item.damage_adjustment)}
                </dd>
            </dl>
        )
    }

    renderDefenceChange() {
        return (
            <dl>
                <dt>Defence</dt>
                <dd className={clsx({
                    'text-green-700 dark:text-green-500' : this.isValueAboveZero(this.props.item.ac_adjustment),
                    'text-red-700 dark:text-red-500' : this.isValueBeloZero(this.props.item.ac_adjustment),
                    'text-gray-700 dark:text-white': this.props.item.ac_adjustment === 0,
                })}>
                    {formatNumber(this.props.item.ac_adjustment)}
                </dd>
            </dl>
        )
    }

    renderHealingChange() {
        if (this.props.item.base_healing_mod === null) {
            return;
        }

        return(
            <dl>
                <dt>Base Healing</dt>
                <dd className='text-green-700 dark:text-green-500'>+{formatNumber(this.props.item.raw_healing)}</dd>
                <dt>Base Healing Mod</dt>
                <dd className='text-green-700 dark:text-green-500'>+{(this.props.item.base_healing_mod * 100).toFixed(2)}%</dd>
                <dt>Resurrection Chance</dt>
                <dd className='text-green-700 dark:text-green-500'>+{(this.props.item.resurrection_chance * 100).toFixed(2)}%</dd>
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

        if (damageBased.includes(this.props.item.type)) {
            return this.renderAttackChange();
        }

        if (this.props.item.type === ItemType.SPELL_HEALING) {
            return this.renderHealingChange();
        }

        return this.renderDefenceChange();
    }


    isValueBeloZero(value: number): boolean {
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
                        'text-green-700 dark:text-green-500' : this.isValueAboveZero(this.props.item[attribute + '_modifier']),
                        'text-red-700 dark:text-red-500' : this.isValueBeloZero(this.props.item[attribute + '_modifier']),
                        'text-gray-700 dark:text-white': this.props.item[attribute + '_modifier'] === 0,
                    })}>
                        {(this.props.item[attribute + '_modifier'] * 100).toFixed(2) + '%'}
                    </dd>
                </>
            )
        })
    }

    render() {
        return (
            <div>
                <h3>
                    {this.getName()}
                </h3>
                <div className='border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3'></div>
                <div className='grid md:grid-cols-2 sm:grid-gcols-1 gap-2'>
                    <div>
                        <dl>
                            {this.renderCoreAttributes()}
                        </dl>
                    </div>
                    <div className='border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 md:hidden sm:block'></div>
                    <div>
                        {this.renderAttackOrDefenceAdjustment()}

                        {
                            this.props.item.crafting_type !== null ?
                                <>
                                    {
                                        this.props.item.type !== ItemType.TRINKET ?
                                            <div className='border-b-2 border-b-gray-200 dark:border-b-gray-600 my-4'></div>
                                        : null
                                    }
                                    <dl>
                                        <dt>Crafting Type:</dt>
                                        <dd>{startCase(this.props.item.crafting_type)}</dd>
                                        <dt>Skill Level Required</dt>
                                        <dd>{this.props.item.skill_level_req}</dd>
                                        <dt>Skill Level Trivial</dt>
                                        <dd>{this.props.item.skill_level_trivial}</dd>
                                    </dl>
                                </>
                            : null
                        }
                    </div>
                </div>
                <div className='border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3'></div>
                <p className='bold'>
                    <a href={"/items/" + this.props.item.id} target="_blank">
                        View more details about this item <i
                        className="fas fa-external-link-alt"></i>
                    </a>
                </p>
            </div>
        )
    }
}
