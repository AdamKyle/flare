import React, {Fragment} from "react";
import Table from "../../../../../components/ui/data-tables/table";
import SkillType from "../../../../../lib/game/character-sheet/types/skills/skill-type";
import SkillsProps from "../../../../../lib/game/character-sheet/types/skills/tables/skills-props";
import PrimaryButton from "../../../../../components/ui/buttons/primary-button";
import DangerButton from "../../../../../components/ui/buttons/danger-button";
import SkillInformation from "../../modals/skills/skill-information";
import {formatNumber} from "../../../../../lib/game/format-number";
import TrainSkill from "../../modals/skills/train-skill";
import {AxiosError, AxiosResponse} from "axios";
import Ajax from "../../../../../lib/ajax/ajax";
import WarningAlert from "../../../../../components/ui/alerts/simple-alerts/warning-alert";
import InfoAlert from "../../../../../components/ui/alerts/simple-alerts/info-alert";

export default class Skills extends React.Component<SkillsProps, any> {

    constructor(props: SkillsProps) {
        super(props);

        this.state = {
            show_skill_details: false,
            show_train_skill: false,
            skill: null,
            stopping: false,
            success_message: null,
        }
    }

    manageTrainSkill(row: any) {
        this.setState({
            show_train_skill: !this.state.show_train_skill,
            skill: typeof row !== 'undefined' ? row : null,
        });
    }

    stopTraining(row: any) {
        this.setState({
            stopping: true,
        }, () => {
            (new Ajax()).setRoute('skill/cancel-train/'+this.props.character_id+'/' + row.id).doAjaxCall('post', (result: AxiosResponse) => {
                this.setState({
                    stopping: false,
                    success_message: result.data.message,
                }, () => {
                    this.props.update_skills(result.data.skills)
                })
            }, (error: AxiosError) => {

            });
        });
    }

    manageSkillDetails(row?: any) {
        this.setState({
            show_skill_details: !this.state.show_skill_details,
            skill: typeof row !== 'undefined' ? row : null,
        });
    }

    setSuccessMessage(message: string) {
        this.setState({
            success_message: message,
        });
    }

    closeSuccessMessage() {
        this.setState({
            success_message: null,
        });
    }

    isAnySkillTraining() {
        return this.props.trainable_skills.filter((skill) => skill.is_training).length > 0;
    }

    buildColumns() {
        return [
            {
                name: 'Name',
                selector: (row: { name: string; }) => row.name,
                sortable: true,
                cell: (row: SkillType) => <span key={row.id + '-' + (Math.random() + 1).toString(36).substring(7)} className='m-auto'>
                    <button onClick={() => this.manageSkillDetails(row)} className='underline'>{row.name}</button>
                </span>
            },
            {
                name: 'Level',
                selector: (row: { level: number }) => row.level,
                sortable: true,
                cell: (row: SkillType) => <span key={row.id + '-' + (Math.random() + 1).toString(36).substring(7)}>{row.level}/{row.max_level}</span>
            },
            {
                name: 'XP',
                selector: (row: { xp: number }) => row.xp,
                sortable: true,
                cell: (row: SkillType) => <span key={row.id + '-' + (Math.random() + 1).toString(36).substring(7)}>{formatNumber(row.xp)}/{formatNumber(row.xp_max)}</span>
            },
            {
                name: 'Training?',
                selector: (row: { is_training: boolean }) => row.is_training ? 'Yes' : 'No',
                sortable: true,
            },
            {
                name: 'Actions',
                selector: (row: any) => '',
                sortable: false,
                cell: (row: SkillType) => <span key={row.id + '-' + (Math.random() + 1).toString(36).substring(7)}>
                    {
                        row.is_training ?
                            <DangerButton button_label={this.state.stopping ? <span>Stopping <i
                                className="fas fa-spinner fa-pulse"></i></span> : 'Stop training'} on_click={() => this.stopTraining(row)} disabled={this.props.is_dead || this.state.stopping || this.props.is_automation_running} />
                        :
                            <PrimaryButton button_label={'Train'} on_click={() => this.manageTrainSkill(row)} disabled={this.props.is_dead || this.isAnySkillTraining() || this.props.is_automation_running} />
                    }
                </span>
            },
        ]
    }

    render() {
        return(
            <Fragment>
                {
                    this.props.is_automation_running ?
                        <div className='mb-4'>
                            <WarningAlert>
                                Automation is running. You cannot train or stop training skills.
                            </WarningAlert>
                        </div>
                        : null
                }

                <div className='mb-4'>
                    <InfoAlert>
                        This section will not update in real time.
                    </InfoAlert>
                </div>

                <Table columns={this.buildColumns()} data={this.props.trainable_skills} dark_table={this.props.dark_table} />

                {
                    this.state.show_skill_details && this.state.skill !== null ?
                        <SkillInformation
                            skill={this.state.skill}
                            manage_modal={this.manageSkillDetails.bind(this)}
                            is_open={this.state.show_skill_details}
                        />
                    : null
                }

                {
                    this.state.show_train_skill && this.state.skill !== null ?
                        <TrainSkill is_open={this.state.show_train_skill}
                                    manage_modal={this.manageTrainSkill.bind(this)}
                                    skill={this.state.skill}
                                    set_success_message={this.setSuccessMessage.bind(this)}
                                    update_skills={this.props.update_skills}
                                    character_id={this.props.character_id}
                        />
                    : null
                }
            </Fragment>
        )
    }

}
