import React from "react";
import Dialogue from "../../../../../components/ui/dialogue/dialogue";
import DangerAlert from "../../../../../components/ui/alerts/simple-alerts/danger-alert";
import { formatNumber } from "../../../../../lib/game/format-number";
import Select from "react-select";
import LoadingProgressBar from "../../../../../components/ui/progress-bars/loading-progress-bar";
import PopOverContainer from "../../../../../components/ui/popover/pop-over-container";
import {AxiosError, AxiosResponse} from "axios";
import Ajax from "../../../../../lib/ajax/ajax";

export default class TrainSkill extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            selected_value: 0.0,
            error_message: null,
            loading: false,
        }
    }

    setSkillToTrain(data: any) {
        this.setState({
            selected_value: data.value,
            error_message: null,
        })
    }

    trainSkill() {
        if (this.state.selected_value === 0.0) {
            return this.setState({
                error_message: 'You must select a % of XP tp sacrifice.'
            });
        }

        this.setState({
            loading: true,
        }, () => {
            (new Ajax()).setRoute('skill/train/' + this.props.character_id).setParameters({
                skill_id: this.props.skill.id,
                xp_percentage: this.state.selected_value,
            }).doAjaxCall('post', (result: AxiosResponse) => {
                this.setState({
                    loading: false,
                }, () => {
                    this.props.set_success_message(result.data.message);
                    this.props.update_skills(result.data.skills);
                    this.props.manage_modal()
                });
            }, (error: AxiosError) => {

            })
        })
    }

    buildItems() {
        return [{
            label: '10%',
            value: 0.10,
        },{
            label: '20%',
            value: 0.20,
        },{
            label: '30%',
            value: 0.30,
        },{
            label: '40%',
            value: 0.40,
        },{
            label: '50%',
            value: 0.50,
        },{
            label: '60%',
            value: 0.60,
        },{
            label: '70%',
            value: 0.70,
        },{
            label: '80%',
            value: 0.80,
        },{
            label: '90%',
            value: 0.90,
        },{
            label: '100%',
            value: 1.0,
        },{
            label: '10%',
            value: 0.10,
        }]
    }

    defaultItem() {
        if (this.state.selected_value === 0.0) {
            return {
                label: 'Please Select',
                value: 0.0
            }
        }

        return {
            label: this.state.selected_value * 100 + '%',
            value: this.state.selected_value
        }
    }

    render() {
        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={this.props.skill.name}
                      primary_button_disabled={this.state.loading}
                      secondary_actions={{
                          secondary_button_label: 'Train Skill',
                          secondary_button_disabled: this.state.loading || this.state.error_message !== null,
                          handle_action: this.trainSkill.bind(this),
                      }}
            >
                {
                    this.props.skill.is_locked ?
                        <DangerAlert additional_css={'mb-4 mt-4'}>
                            This skill is locked. You will need to complete a quest to unlock it.
                        </DangerAlert>
                    : null
                }

                {
                    this.state.error_message !== null ?
                        <DangerAlert additional_css={'mb-4 mt-4'}>
                            {this.state.error_message}
                        </DangerAlert>
                    : null
                }

                <p className='mb-4'>
                    {this.props.skill.description}
                </p>

                <dl>
                    <dt>Current Level</dt>
                    <dd>{this.props.skill.level}</dd>
                    <dt>Max Level</dt>
                    <dd>{this.props.skill.max_level}</dd>
                    <dt className='flex items-center'>
                        <span>XP Towards</span>
                        <div>
                            <PopOverContainer icon={'fas fa-info-circle'} icon_label={'Help'}>
                                <h3 className='text-gray-700 dark:text-gray-200'>Sacrifice XP</h3>
                                <p className='my-2 text-gray-700 dark:text-gray-200'>
                                    By setting this to a percentage, any XP gained from monsters, Adventures or Exploration
                                    will be reduced <strong>BEFORE</strong> additional modifiers and quest items are applied. This amount will then be
                                    applied to the skill XP.
                                </p>
                                <p className='my-2 text-gray-700 dark:text-gray-200'>
                                    As you level these skills the XP will go up by 100 per skill level, that is at level 1, you need 100XP, but at level 10, you need 1000.
                                </p>
                                <p className='my-2 text-gray-700 dark:text-gray-200'>
                                    From level 1-10, you will get a base of 25XP + what you choose to sacrifice, at level 10 to 99, you get 100 XP. Level 100 - 499 you get 500XP and finally level 500-999 you get 750XP.
                                </p>
                            </PopOverContainer>
                        </div>
                    </dt>
                    <dd>
                        <Select
                            onChange={this.setSkillToTrain.bind(this)}
                            options={this.buildItems()}
                            menuPosition={'absolute'}
                            menuPlacement={'bottom'}
                            styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                            menuPortalTarget={document.body}
                            value={this.defaultItem()}
                        />
                    </dd>
                    <dt>Skill Bonus</dt>
                    <dd>{(this.props.skill.skill_bonus * 100).toFixed(2)}%</dd>
                    <dt>XP</dt>
                    <dd>{formatNumber(this.props.skill.xp)} / {formatNumber(this.props.skill.xp_max)}</dd>
                </dl>

                {
                    this.state.loading ?
                        <LoadingProgressBar />
                    : null
                }

            </Dialogue>
        );
    }
}
