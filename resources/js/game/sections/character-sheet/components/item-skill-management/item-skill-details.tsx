import React from "react";
import ItemSkillDetailsProps from "./types/item-skill-details-props";
import clsx from 'clsx';
import PrimaryButton from "../../../../components/ui/buttons/primary-button";
import DangerButton from "../../../../components/ui/buttons/danger-button";
import SuccessButton from "../../../../components/ui/buttons/success-button";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import SuccessAlert from "../../../../components/ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../../../components/ui/alerts/simple-alerts/danger-alert";

export default class ItemSkillDetails extends React.Component<ItemSkillDetailsProps, any> {
    constructor(props: ItemSkillDetailsProps) {
        super(props);

        this.state = {
            loading: false,
            success_message: null,
            error_message: null,
        }
    }

    render() {
        const skillData = this.props.skill_progression_data;

        return (
            <>
                <h2>{this.props.skill_progression_data.item_skill.name}</h2>
                {
                    this.state.success_message !== null ?
                        <SuccessAlert additional_css="my-4">
                            {this.state.success_message}
                        </SuccessAlert>
                    : null
                }

                {
                    this.state.error_message !== null ?
                        <DangerAlert additional_css="my-4">
                            {this.state.error_message}
                        </DangerAlert>
                    : null
                }
                
                <p className='my-4'>{this.props.skill_progression_data.item_skill.description}</p>
                <p className="mb-4">For more infomatio please refer to the <a href='/information/item-skills' target='_blank' >Item Skills help docs <i
                        className="fas fa-external-link-alt"></i></a>.</p>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <span className="my-4 font-bold">Progression Data</span>
                <dl>
                    <dt>Level (Current/Max):</dt>
                    <dd>{skillData.current_level}/{skillData.item_skill.max_level}</dd> 
                    <dt>Kill Count (Current/Needed):</dt>
                    <dd>
                        {
                            skillData.current_level === skillData.item_skill.max_level ?
                                'You have maxed this skill'
                            : skillData.current_kill + '/' + skillData.item_skill.total_kills_needed
                        }
                    </dd>    
                </dl>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <div className="grid lg:grid-cols-2 gap-2">
                    <div>
                        <dl>
                            <dt>Str Modifier</dt>
                            <dd className={
                                clsx({
                                    'text-green-600 dark:text-green-400': skillData.item_skill.str_mod > 0
                                })
                            }>{skillData.str_mod * 100}% {skillData.item_skill.str_mod > 0 ? '(+'+(skillData.item_skill.str_mod * 100)+'%/Lv)' : ''}</dd>

                            <dt>Dex Modifier</dt>
                            <dd className={
                                clsx({
                                    'text-green-600 dark:text-green-400': skillData.item_skill.dex_mod > 0
                                })
                            }>{skillData.dex_mod * 100}% {skillData.item_skill.dex_mod > 0 ? '(+'+(skillData.item_skill.dex_mod * 100)+'%/Lv)' : ''}</dd>

                            <dt>Dur Modifier</dt>
                            <dd className={
                                clsx({
                                    'text-green-600 dark:text-green-400': skillData.item_skill.dur_mod > 0
                                })
                            }>{skillData.dur_mod * 100}% {skillData.item_skill.dur_mod > 0 ? '(+'+(skillData.item_skill.dur_mod * 100)+'%/Lv)' : ''}</dd>

                            <dt>Agi Modifier</dt>
                            <dd className={
                                clsx({
                                    'text-green-600 dark:text-green-400': skillData.item_skill.agi_mod > 0
                                })
                            }>{skillData.agi_mod * 100}% {skillData.item_skill.agi_mod > 0 ? '(+'+(skillData.item_skill.agi_mod * 100)+'%/Lv)' : ''}</dd>

                            <dt>Int Modifier</dt>
                            <dd className={
                                clsx({
                                    'text-green-600 dark:text-green-400': skillData.item_skill.int_mod > 0
                                })
                            }>{skillData.int_mod * 100}% {skillData.item_skill.int_mod > 0 ? '(+'+(skillData.item_skill.int_mod * 100)+'%/Lv)' : ''}</dd>

                            <dt>Chr Modifier</dt>
                            <dd className={
                                clsx({
                                    'text-green-600 dark:text-green-400': skillData.item_skill.chr_mod > 0
                                })
                            }>{skillData.chr_mod * 100}% {skillData.item_skill.chr_mod > 0 ? '(+'+(skillData.item_skill.chr_mod * 100)+'%/Lv)' : ''}</dd>

                            <dt>Focus Modifier</dt>
                            <dd className={
                                clsx({
                                    'text-green-600 dark:text-green-400': skillData.item_skill.focus_mod > 0
                                })
                            }>{skillData.focus_mod * 100}% {skillData.item_skill.focus_mod > 0 ? '(+'+(skillData.item_skill.focus_mod * 100)+'%/Lv)' : ''}</dd>
                        </dl>
                    </div>
                    <div className='block lg:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <div>
                        <dl>
                            <dt>Attack Modifier</dt>
                            <dd className={
                                clsx({
                                    'text-green-600 dark:text-green-400': skillData.item_skill.base_attack_mod > 0
                                })
                            }>{skillData.base_attack_mod * 100}% {skillData.item_skill.base_attack_mod > 0 ? '(+'+(skillData.item_skill.base_attack_mod * 100)+'%/Lv)' : ''}</dd>

                            <dt>AC Modifier</dt>
                            <dd className={
                                clsx({
                                    'text-green-600 dark:text-green-400': skillData.item_skill.base_ac_mod > 0
                                })
                            }>{skillData.base_ac_mod * 100}% {skillData.item_skill.base_ac_mod > 0 ? '(+'+(skillData.item_skill.base_ac_mod * 100)+'%/Lv)' : ''}</dd>

                            <dt>Healing Modifier</dt>
                            <dd className={
                                clsx({
                                    'text-green-600 dark:text-green-400': skillData.item_skill.base_healing_mod > 0
                                })
                            }>{skillData.base_healing_mod * 100}% {skillData.item_skill.base_healing_mod > 0 ? '(+'+(skillData.item_skill.base_healing_mod * 100)+'%/Lv)' : ''}</dd>
                        </dl>
                    </div>
                </div>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                {
                    this.state.is_loading ?
                        <LoadingProgressBar />
                    : null
                }
                <div className="flex space-x-4 flex-row justify-start">
                    
                    {
                        this.props.skill_progression_data.is_training ?
                            <PrimaryButton button_label={'Stop Training Skill'} on_click={() => {}} />
                        :
                            <SuccessButton button_label={'Train Skill'} on_click={() => {}} />
                    }
                    
                    
                    <DangerButton button_label={'Close Skill Management'} on_click={() => this.props.manage_skill_details(null)} />
                </div>
            </>
        )
    }
}