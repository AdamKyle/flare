import React, {ReactNode} from "react";
import {isWeaponType} from "./helpers/is-weapon-type";
import {formatNumber} from "../../lib/game/format-number";
import {isArmourType} from "./helpers/is-armour-type";
import {startCase} from "lodash";
import ItemDefinition from "./deffinitions/item-definition";
import {ItemType} from "./enums/item-type";
import ItemToEquip from "../item-comparison/deffinitions/item-to-equip";

interface ItemProps {
    item: ItemDefinition | ItemToEquip;
}

export default class Item extends React.Component<ItemProps, any> {
    constructor(props: ItemProps) {
        super(props);
    }

    getName() {
        return <span className='text-gray-600 dark:text-white'>
            {this.props.item.name}
        </span>
    }

    renderItemTypeData(): ReactNode {
        if (isWeaponType(this.props.item.type)) {
            return (
                <>
                    <dt>Base Damage</dt>
                    <dd className='text-green-700 dark:text-green-500'>+{formatNumber(this.props.item.raw_damage)}</dd>
                    <dt>Base Damage Mod</dt>
                    <dd className='text-green-700 dark:text-green-500'>+{(this.props.item.base_damage_mod * 100).toFixed(2)}%</dd>
                </>
            )
        }

        if (isArmourType(this.props.item.type)) {
            if (this.props.item.base_ac_mod === null) {
                return;
            }

            return (
                <>
                    <dt>Base AC</dt>
                    <dd className='text-green-700 dark:text-green-500'>+{formatNumber(this.props.item.raw_ac)}</dd>
                    <dt>Base AC Mod</dt>
                    <dd className='text-green-700 dark:text-green-500'>+{(this.props.item.base_ac_mod * 100).toFixed(2)}%</dd>
                </>
            )
        }

        if (this.props.item.type === ItemType.SPELL_HEALING) {

            if (this.props.item.base_healing_mod === null) {
                return;
            }

            return (
                <>
                    <dt>Base Healing</dt>
                    <dd className='text-green-700 dark:text-green-500'>+{formatNumber(this.props.item.raw_healing)}</dd>
                    <dt>Base Healing Mod</dt>
                    <dd className='text-green-700 dark:text-green-500'>+{(this.props.item.base_healing_mod * 100).toFixed(2)}%</dd>
                    <dt>Resurrection Chance</dt>
                    <dd className='text-green-700 dark:text-green-500'>+{(this.props.item.resurrection_chance * 100).toFixed(2)}%</dd>
                </>
            )
        }

        return null;
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
                            <dt>Strength</dt>
                            <dd className='text-green-700 dark:text-green-500'>+{(this.props.item.str_modifier * 100).toFixed(2)}%</dd>
                            <dt>Dexterity</dt>
                            <dd className='text-green-700 dark:text-green-500'>+{(this.props.item.dex_modifier * 100).toFixed(2)}%</dd>
                            <dt>Durability</dt>
                            <dd className='text-green-700 dark:text-green-500'>+{(this.props.item.dur_modifier * 100).toFixed(2)}%</dd>
                            <dt>Intelligence</dt>
                            <dd className='text-green-700 dark:text-green-500'>+{(this.props.item.int_modifier * 100).toFixed(2)}%</dd>
                            <dt>Charisma</dt>
                            <dd className='text-green-700 dark:text-green-500'>+{(this.props.item.chr_modifier * 100).toFixed(2)}%</dd>
                            <dt>Agility</dt>
                            <dd className='text-green-700 dark:text-green-500'>+{(this.props.item.agi_modifier * 100).toFixed(2)}%</dd>
                            <dt>Focus</dt>
                            <dd className='text-green-700 dark:text-green-500'>+{(this.props.item.focus_modifier * 100).toFixed(2)}%</dd>
                        </dl>
                    </div>
                    <div className='border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 md:hidden sm:block'></div>
                    <div>
                        <dl>
                            {this.renderItemTypeData()}
                        </dl>
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
