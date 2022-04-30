import React, {Fragment} from "react";
import Table from "../../../../../components/ui/data-tables/table";
import SkillType from "../../../../../lib/game/character-sheet/types/skills/skill-type";
import SkillsProps from "../../../../../lib/game/character-sheet/types/skills/tables/skills-props";
import PrimaryButton from "../../../../../components/ui/buttons/primary-button";
import DangerButton from "../../../../../components/ui/buttons/danger-button";
import SkillInformation from "../../modals/skills/skill-information";
import {formatNumber} from "../../../../../lib/game/format-number";

export default class Skills extends React.Component<SkillsProps, any> {

    constructor(props: SkillsProps) {
        super(props);

        this.state = {
            show_skill_details: false,
            skill: null,
        }
    }

    trainSkill(id: number) {
    }

    stopTraining(id: number) {

    }

    manageSkillDetails(row?: any) {
        this.setState({
            show_skill_details: !this.state.show_skill_details,
            skill: typeof row !== 'undefined' ? row : null,
        });
    }

    buildColumns() {
        return [
            {
                name: 'Name',
                selector: (row: { name: string; }) => row.name,
                sortable: true,
                cell: (row: SkillType) => <span key={row.id + '-' + (Math.random() + 1).toString(36).substring(7)}>
                    <button onClick={() => this.manageSkillDetails(row)}>{row.name}</button>
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
                            <DangerButton button_label={'Stop Training'} on_click={() => this.stopTraining(row.id)} disabled={this.props.is_dead} />
                        :
                            <PrimaryButton button_label={'Train'} on_click={() => this.trainSkill(row.id)} disabled={this.props.is_dead} />
                    }
                </span>
            },
        ]
    }

    render() {
        return(
            <Fragment>
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
            </Fragment>
        )
    }

}
