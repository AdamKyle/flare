import React from "react";
import Dialogue from "../../../../../components/ui/dialogue/dialogue";
import {formatNumber} from "../../../../../lib/game/format-number";

export default class ItemAffixDetails extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    render() {
        return(
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={this.props.affix.name}
                      large_modal={true}
            >
                <div className='max-h-[350px] overflow-y-scroll'>
                    <div className='mb-4 mt-4 text-sky-700 dark:text-sky-500' dangerouslySetInnerHTML={{__html: this.props.affix.description}} />

                    <div className='grid md:grid-cols-2 gap-3'>
                        <div>
                            <h4 className='text-sky-600 dark:text-sky-500'>Stats</h4>
                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <dl>
                                <dt>Str Modifier</dt>
                                <dd>{(this.props.affix.str_mod * 100).toFixed(2)}%</dd>
                                <dt>Dex Modifier</dt>
                                <dd>{(this.props.affix.dex_mod * 100).toFixed(2)}%</dd>
                                <dt>Agi Modifier</dt>
                                <dd>{(this.props.affix.agi_mod * 100).toFixed(2)}%</dd>
                                <dt>Chr Modifier</dt>
                                <dd>{(this.props.affix.chr_mod * 100).toFixed(2)}%</dd>
                                <dt>Dur Modifier</dt>
                                <dd>{(this.props.affix.dur_mod * 100).toFixed(2)}%</dd>
                                <dt>Int Modifier</dt>
                                <dd>{(this.props.affix.int_mod * 100).toFixed(2)}%</dd>
                                <dt>Focus Modifier</dt>
                                <dd>{(this.props.affix.focus_mod * 100).toFixed(2)}%</dd>
                            </dl>

                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <h4 className='text-sky-600 dark:text-sky-500'>Skill Modifiers</h4>
                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <dl>
                                <dt>Skill Name</dt>
                                <dd>{this.props.affix.skill_name !== null ? this.props.affix.skill_name : 'N/A'}</dd>
                                <dt>Skill XP Bonus</dt>
                                <dd>{(this.props.affix.skill_training_bonus * 100).toFixed(2)}%</dd>
                                <dt>Skill Bonus</dt>
                                <dd>{(this.props.affix.skill_bonus * 100).toFixed(2)}%</dd>
                            </dl>
                        </div>
                        <div>
                            <h4 className='text-sky-600 dark:text-sky-500'>Damage/AC/Healing Modifiers</h4>
                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <dl>
                                <dt>Base Attack Modifier</dt>
                                <dd>{this.props.affix.base_damage_mod !== null ? this.props.affix.skill_name : 'N/A'}</dd>
                                <dt>Base AC Modifier</dt>
                                <dd>{(this.props.affix.base_ac_mod * 100).toFixed(2)}%</dd>
                                <dt>Base Healing Modifier</dt>
                                <dd>{(this.props.affix.base_healing_mod * 100).toFixed(2)}%</dd>
                            </dl>

                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <h4 className='text-sky-600 dark:text-sky-500'>Class Bonus</h4>
                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <dl>
                                <dt>Class Bonus</dt>
                                <dd>{this.props.affix.class_bonus !== null ? (this.props.affix.class_bonus * 100).toFixed(2) + '%' : 'N/A'}</dd>
                            </dl>

                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <h4 className='text-sky-600 dark:text-sky-500'>Misc Skill Modifiers</h4>
                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <dl>
                                <dt>Base Attack Modifier</dt>
                                <dd>{(this.props.affix.base_damage_mod_bonus * 100).toFixed(2)}%</dd>
                                <dt>Base AC Modifier</dt>
                                <dd>{(this.props.affix.base_ac_mod_bonus * 100).toFixed(2)}%</dd>
                                <dt>Base Healing Modifier</dt>
                                <dd>{(this.props.affix.base_healing_mod_bonus * 100).toFixed(2)}%</dd>
                                <dt>Fight Timeout Modifier</dt>
                                <dd>{(this.props.affix.fight_time_out_mod_bonus * 100).toFixed(2)}%</dd>
                                <dt>Move Timeout Modifier</dt>
                                <dd>{(this.props.affix.move_time_out_mod_bonus * 100).toFixed(2)}%</dd>
                            </dl>
                        </div>
                    </div>

                    <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>

                    <div className='grid md:grid-cols-2 gap-3'>
                        <div>
                            <h4 className='text-sky-600 dark:text-sky-500'>Damage</h4>
                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <dl>
                                <dt>Damage:</dt>
                                <dd>{formatNumber(this.props.affix.damage)}</dd>
                                <dt>Is Damage Irresistible?:</dt>
                                <dd>{this.props.affix.irresistible_damage ? 'Yes' : 'No'}</dd>
                                <dt>Can Stack:</dt>
                                <dd>{this.props.affix.damage_can_stack ? 'Yes' : 'No'}</dd>
                            </dl>

                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <h4 className='text-sky-600 dark:text-sky-500'>Stat Reduction</h4>
                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <dl>
                                <dt>Str Reduction:</dt>
                                <dd>{(this.props.affix.str_reduction * 100).toFixed(2)}%</dd>
                                <dt>Dex Reduction:</dt>
                                <dd>{(this.props.affix.dex_reduction * 100).toFixed(2)}%</dd>
                                <dt>Dur Reduction:</dt>
                                <dd>{(this.props.affix.dur_reduction * 100).toFixed(2)}%</dd>
                                <dt>Int Reduction:</dt>
                                <dd>{(this.props.affix.int_reduction * 100).toFixed(2)}%</dd>
                                <dt>Chr Reduction:</dt>
                                <dd>{(this.props.affix.chr_reduction * 100).toFixed(2)}%</dd>
                                <dt>Agi Reduction:</dt>
                                <dd>{(this.props.affix.agi_reduction * 100).toFixed(2)}%</dd>
                                <dt>Focus Reduction:</dt>
                                <dd>{(this.props.affix.focus_reduction * 100).toFixed(2)}%</dd>
                            </dl>
                        </div>
                        <div>
                            <h4 className='text-sky-600 dark:text-sky-500'>Life Stealing</h4>
                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <dl>
                                <dt>Damage:</dt>
                                <dd>{(this.props.affix.steal_life_amount * 100).toFixed(2)}%</dd>
                            </dl>

                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <h4 className='text-sky-600 dark:text-sky-500'>Entrance</h4>
                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <dl>
                                <dt>Chance:</dt>
                                <dd>{(this.props.affix.entranced_chance * 100).toFixed(2)}%</dd>
                            </dl>

                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <h4 className='text-sky-600 dark:text-sky-500'>Devouring Light</h4>
                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <dl>
                                <dt>Devouring Light Chance:</dt>
                                <dd>{(this.props.affix.devouring_light * 100).toFixed(2)}%</dd>
                            </dl>

                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <h4 className='text-sky-600 dark:text-sky-500'>Skill Reduction</h4>
                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <dl>
                                <dt>Skills Affected:</dt>
                                <dd>Accuracy, Dodge, Casting Accuracy and Criticality</dd>
                                <dt>Skills Reduced By:</dt>
                                <dd>{(this.props.affix.skill_reduction * 100).toFixed(2)}%</dd>
                            </dl>

                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <h4 className='text-sky-600 dark:text-sky-500'>Resistance Reduction</h4>
                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <dl>
                                <dt>Reduction:</dt>
                                <dd>{(this.props.affix.resistance_reduction * 100).toFixed(2)}%</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </Dialogue>
        )
    }
}
