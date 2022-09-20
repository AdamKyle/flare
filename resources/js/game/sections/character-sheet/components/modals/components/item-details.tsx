import React, {Fragment} from "react";
import {formatNumber} from "../../../../../lib/game/format-number";
import ItemAffixDetails from "./item-affix-details";
import ItemHolyDetails from "./item-holy-details";
import OrangeButton from "../../../../../components/ui/buttons/orange-button";

export default class ItemDetails extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            affix: null,
            view_affix: false,
            holy_stacks: null,
            view_stacks: false,
        }
    }

    manageAffixModal(affix?: any) {
        this.setState({
            affix: typeof affix !== 'undefined' ? affix : null,
            view_affix: !this.state.view_affix,
        });
    }

    manageHolyStacksDetails(holyStacks?: any) {
        this.setState({
            holy_stacks: typeof holyStacks !== 'undefined' ? holyStacks : null,
            view_stacks: !this.state.view_stacks,
        });
    }

    render() {
        return (
            <div className='max-h-[400px] overflow-y-auto'>
                <div className='mb-4 mt-4 text-sky-700 dark:text-sky-500' dangerouslySetInnerHTML={{__html: this.props.item.description}} />

                <div className='grid md:grid-cols-3 gap-3 mb-4'>
                    <div>
                        <h4 className='text-sky-600 dark:text-sky-500'>Stats</h4>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                        <dl>
                            <dt>Str Modifier</dt>
                            <dd>{(this.props.item.str_modifier * 100).toFixed(2)}%</dd>
                            <dt>Dex Modifier</dt>
                            <dd>{(this.props.item.dex_modifier * 100).toFixed(2)}%</dd>
                            <dt>Agi Modifier</dt>
                            <dd>{(this.props.item.agi_modifier * 100).toFixed(2)}%</dd>
                            <dt>Chr Modifier</dt>
                            <dd>{(this.props.item.chr_modifier * 100).toFixed(2)}%</dd>
                            <dt>Dur Modifier</dt>
                            <dd>{(this.props.item.dur_modifier * 100).toFixed(2)}%</dd>
                            <dt>Int Modifier</dt>
                            <dd>{(this.props.item.int_modifier * 100).toFixed(2)}%</dd>
                            <dt>Focus Modifier</dt>
                            <dd>{(this.props.item.focus_modifier * 100).toFixed(2)}%</dd>
                        </dl>
                    </div>
                    <div>
                        <h4 className='text-sky-600 dark:text-sky-500'>Modifiers</h4>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                        <dl>
                            <dt>Base Damage</dt>
                            <dd>{this.props.item.base_damage > 0 ? formatNumber(this.props.item.base_damage) : 0}</dd>
                            <dt>Base Ac</dt>
                            <dd>{this.props.item.base_ac > 0 ? formatNumber(this.props.item.base_ac) : 0}</dd>
                            <dt>Base Healing</dt>
                            <dd>{this.props.item.base_healing > 0 ? formatNumber(this.props.item.base_healing) : 0}</dd>
                            <dt>Base Damage Mod</dt>
                            <dd>{(this.props.item.base_damage_mod * 100).toFixed(2)}%</dd>
                            <dt>Base Ac Mod</dt>
                            <dd>{(this.props.item.base_ac_mod * 100).toFixed(2)}%</dd>
                            <dt>Base Healing Mod</dt>
                            <dd>{(this.props.item.base_healing_mod * 100).toFixed(2)}%</dd>
                        </dl>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                        <h4 className='text-sky-600 dark:text-sky-500'>Skill Modifiers</h4>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                        <dl>
                            <dt>Effects Skill</dt>
                            <dd>{this.props.item.skill_name !== null ? this.props.item.skill_name : 'N/A'}</dd>
                            <dt>Skill Bonus</dt>
                            <dd>{(this.props.item.skill_bonus * 100).toFixed(2)}%</dd>
                            <dt>Skill XP Bonus</dt>
                            <dd>{(this.props.item.skill_training_bonus * 100).toFixed(2)}%</dd>
                        </dl>
                    </div>
                    <div>
                        <h4 className='text-sky-600 dark:text-sky-500'>Evasion and Reductions</h4>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                        <dl>
                            <dt>Spell Evasion</dt>
                            <dd>{(this.props.item.spell_evasion * 100).toFixed(2)}%</dd>
                            <dt>Healing Reduction</dt>
                            <dd>{(this.props.item.healing_reduction * 100).toFixed(2)}%</dd>
                            <dt>Affix Dmg. Reduction</dt>
                            <dd>{(this.props.item.affix_damage_reduction * 100).toFixed(2)}%</dd>
                        </dl>

                        {
                            this.props.item.affix_count > 0 ?
                                <Fragment>
                                    <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                                    <h4 className='text-sky-600 dark:text-sky-500'>Attached Affixes</h4>
                                    <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                                    <div className='mt-4'>
                                        <div className={"mb-4"}>
                                            {
                                                this.props.item.item_prefix !== null ?
                                                    <OrangeButton button_label={this.props.item.item_prefix.name}
                                                                  on_click={() => this.manageAffixModal(this.props.item.item_prefix)}
                                                                  additional_css={'w-1/2'}/>
                                                    : null
                                            }
                                        </div>
                                        <div>
                                            {
                                                this.props.item.item_suffix !== null ?
                                                    <OrangeButton button_label={this.props.item.item_suffix.name}
                                                                  on_click={() => this.manageAffixModal(this.props.item.item_suffix)}
                                                                  additional_css={'w-1/2'}/>
                                                    : null
                                            }
                                        </div>
                                    </div>
                                </Fragment>
                            : null
                        }

                    </div>
                </div>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <div className='grid md:grid-cols-3 gap-3 mb-4'>
                    <div>
                        <h4 className='text-sky-600 dark:text-sky-500'>Devouring Chance</h4>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                        <dl>
                            <dt>Devouring Light</dt>
                            <dd>{(this.props.item.devouring_light * 100).toFixed(2)}%</dd>
                            <dt>Devouring Darkness</dt>
                            <dd>{(this.props.item.devouring_darkness * 100).toFixed(2)}%</dd>
                        </dl>
                    </div>
                    <div>
                        <h4 className='text-sky-600 dark:text-sky-500'>Resurrection Chance</h4>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                        <dl>
                            <dt>Chance</dt>
                            <dd>{(this.props.item.resurrection_chance * 100).toFixed(2)}%</dd>
                        </dl>
                    </div>
                    <div>
                        <h4 className='text-sky-600 dark:text-sky-500'>Holy Info</h4>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                        <p className="mb-4">Indicates how many can be applied to the item, via the <a
                            href="/information/holy-items" target="_blank"><i className="fas fa-external-link-alt"></i>Purgatory
                            Smith Work Bench.</a></p>
                        <dl>
                            <dt>Holy Stacks</dt>
                            <dd>{this.props.item.holy_stacks}</dd>
                            <dt>Holy Stacks Applied</dt>
                            <dd>{this.props.item.holy_stacks_applied}</dd>
                            {
                                this.props.item.holy_stacks_applied > 0 ?
                                    <Fragment>
                                        <dt>Holy Stack Bonus</dt>
                                        <dd>{(this.props.item.holy_stack_stat_bonus * 100).toFixed(2)}%</dd>
                                        <dt>Holy Stack Stat Bonus</dt>
                                        <dd>{(this.props.item.holy_stack_stat_bonus * 100).toFixed(2)}%</dd>
                                        <dt>Holy Stack Break Down</dt>
                                        <dd>
                                            <button type='button' className='text-orange-600 dark:text-orange-500 hover:text-orange-700 dark:hover:text-orange-400' onClick={() => this.manageHolyStacksDetails(this.props.item.applied_stacks)}>View Details</button>
                                        </dd>
                                    </Fragment>
                                : null
                            }
                        </dl>
                    </div>
                </div>
                <div className='grid md:grid-cols-3 gap-3 mb-4'>
                    <div>
                        <h4 className='text-sky-600 dark:text-sky-500'>Ambush Info</h4>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                        <dl>
                            <dt>Chance</dt>
                            <dd>{(this.props.item.ambush_chance * 100).toFixed(2)}%</dd>
                            <dt>Resistance</dt>
                            <dd>{(this.props.item.ambush_resistance * 100).toFixed(2)}%</dd>
                        </dl>
                    </div>
                    <div>
                        <h4 className='text-sky-600 dark:text-sky-500'>Counter</h4>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                        <dl>
                            <dt>Chance</dt>
                            <dd>{(this.props.item.counter_chance * 100).toFixed(2)}%</dd>
                            <dt>Resistance</dt>
                            <dd>{(this.props.item.counter_resistance * 100).toFixed(2)}%</dd>
                        </dl>
                    </div>
                </div>

                {
                    this.state.view_affix && this.state.affix !== null ?
                        <ItemAffixDetails is_open={this.state.view_affix} affix={this.state.affix} manage_modal={this.manageAffixModal.bind(this)} />
                    : null
                }

                {
                    this.state.view_stacks && this.state.holy_stacks !== null ?
                        <ItemHolyDetails is_open={this.state.view_stacks} holy_stacks={this.state.holy_stacks} manage_modal={this.manageHolyStacksDetails.bind(this)} />
                    : null
                }
            </div>
        )
    }
}
