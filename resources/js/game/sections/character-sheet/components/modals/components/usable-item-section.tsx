import React, {Fragment} from "react";
import clsx from "clsx";

export default class UsableItemSection extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <Fragment>
                <p className='mt-4 mb-4'>
                    {this.props.item.description}
                </p>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                {
                    this.props.item.damages_kingdoms ?
                        <Fragment>
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
                                    'text-green-700 dark:text-green-600': this.props.item.kingdom_damage > 0
                                })}>{(this.props.item.kingdom_damage * 100).toFixed(2)}%</dd>
                            </dl>
                        </Fragment>
                    :
                        <Fragment>
                            <p className='mb-4 text-sky-700 dark:text-sky-500'>
                                <strong>Lasts For: </strong> {this.props.item.lasts_for} Minutes.
                            </p>
                            <div className='grid grid-cols-2 gap-2'>
                                <div>
                                    {
                                        this.props.item.stat_increase > 0 ?
                                            <dl>
                                                <dt>All Stat ncrease %</dt>
                                                <dd className='text-green-700 dark:text-green-600'>{(this.props.item.stat_increase * 100).toFixed(2)}%</dd>
                                            </dl>
                                        :
                                            <dl>
                                                <dt>Str. Modifier</dt>
                                                <dd className={clsx({
                                                    'text-green-700 dark:text-green-600': this.props.item.str_mod > 0
                                                })}>{(this.props.item.str_mod * 100).toFixed(2)}%</dd>
                                                <dt>Dex. Modifier</dt>
                                                <dd className={clsx({
                                                    'text-green-700 dark:text-green-600': this.props.item.dex_mod > 0
                                                })}>{(this.props.item.dex_mod * 100).toFixed(2)}%</dd>
                                                <dt>Dur. Modifier</dt>
                                                <dd className={clsx({
                                                    'text-green-700 dark:text-green-600': this.props.item.dur_mod > 0
                                                })}>{(this.props.item.dur_mod * 100).toFixed(2)}%</dd>
                                                <dt>Int. Modifier</dt>
                                                <dd className={clsx({
                                                    'text-green-700 dark:text-green-600': this.props.item.int_mod > 0
                                                })}>{(this.props.item.int_mod * 100).toFixed(2)}%</dd>
                                                <dt>Chr. Modifier</dt>
                                                <dd className={clsx({
                                                    'text-green-700 dark:text-green-600': this.props.item.chr_mod > 0
                                                })}>{(this.props.item.chr_mod * 100).toFixed(2)}%</dd>
                                                <dt>Agi. Modifier</dt>
                                                <dd className={clsx({
                                                    'text-green-700 dark:text-green-600': this.props.item.agi_mod > 0
                                                })}>{(this.props.item.agi_mod * 100).toFixed(2)}%</dd>
                                                <dt>Focus Modifier</dt>
                                                <dd className={clsx({
                                                    'text-green-700 dark:text-green-600': this.props.item.focus_mod > 0
                                                })}>{(this.props.item.focus_mod * 100).toFixed(2)}%</dd>
                                            </dl>
                                    }

                                </div>

                                <div>
                                    <dl>
                                        <dt>Base Ac Mod</dt>
                                        <dd className={clsx({
                                            'text-green-700 dark:text-green-600': this.props.item.base_ac_mod > 0
                                        })}>{(this.props.item.base_ac_mod * 100).toFixed(2)}%</dd>
                                        <dt>Base Dmg. Mod</dt>
                                        <dd className={clsx({
                                            'text-green-700 dark:text-green-600': this.props.item.base_damage_mod > 0
                                        })}>{(this.props.item.base_damage_mod * 100).toFixed(2)}%</dd>
                                        <dt>Base Healing Mod</dt>
                                        <dd className={clsx({
                                            'text-green-700 dark:text-green-600': this.props.item.base_healing_mod > 0
                                        })}>{(this.props.item.base_healing_mod * 100).toFixed(2)}%</dd>
                                    </dl>

                                    <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                                    <h3>Skills</h3>
                                    <dl>
                                        <dt>Ac Mod</dt>
                                        <dd className={clsx({
                                            'text-green-700 dark:text-green-600': this.props.item.base_ac_mod_bonus > 0
                                        })}>{(this.props.item.base_ac_mod_bonus * 100).toFixed(2)}%</dd>
                                        <dt>Base Dmg. Mod</dt>
                                        <dd className={clsx({
                                            'text-green-700 dark:text-green-600': this.props.item.base_damage_mod_bonus > 0
                                        })}>{(this.props.item.base_damage_mod_bonus * 100).toFixed(2)}%</dd>
                                        <dt>Base healing Mod</dt>
                                        <dd className={clsx({
                                            'text-green-700 dark:text-green-600': this.props.item.base_healing_mod_bonus > 0
                                        })}>{(this.props.item.base_healing_mod_bonus * 100).toFixed(2)}%</dd>
                                    </dl>
                                </div>
                            </div>
                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <dl>
                                <dt>Skill Bonus %</dt>
                                <dd className={clsx({
                                    'text-green-700 dark:text-green-600': this.props.item.increase_skill_bonus_by > 0
                                })}>{(this.props.item.increase_skill_bonus_by * 100).toFixed(2)}%</dd>
                                <dt>Skill XP Bonus %</dt>
                                <dd className={clsx({
                                    'text-green-700 dark:text-green-600': this.props.item.increase_skill_bonus_by > 0
                                })}>{(this.props.item.increase_skill_training_bonus_by * 100).toFixed(2)}%</dd>
                                <dt>Fight Timeout Bonus %</dt>
                                <dd className={clsx({
                                    'text-green-700 dark:text-green-600': this.props.item.fight_time_out_mod_bonus > 0
                                })}>{(this.props.item.fight_time_out_mod_bonus * 100).toFixed(2)}%</dd>
                                <dt>Move Timeout Bonus %</dt>
                                <dd className={clsx({
                                    'text-green-700 dark:text-green-600': this.props.item.move_time_out_mod_bonus > 0
                                })}>{(this.props.item.move_time_out_mod_bonus * 100).toFixed(2)}%</dd>
                                <dt>Affects the following Skills</dt>
                                <dd>{this.props.item.skills.join(', ')}</dd>
                            </dl>
                        </Fragment>
                }

            </Fragment>
        )
    }
}
