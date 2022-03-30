import React from "react";
import Table from "../../../../../components/ui/data-tables/table";
import SkillType from "../../../../../lib/game/character-sheet/types/skills/skill-type";
import SkillsProps from "../../../../../lib/game/character-sheet/types/skills/tables/skills-props";
import PrimaryButton from "../../../../../components/ui/buttons/primary-button";
import DangerButton from "../../../../../components/ui/buttons/danger-button";

export default class Skills extends React.Component<SkillsProps, any> {

    constructor(props: SkillsProps) {
        super(props);
    }

    trainSkill(id: number) {
    }

    stopTraining(id: number) {

    }

    buildColumns() {
        return [
            {
                name: 'Name',
                selector: (row: { name: string; }) => row.name,
                sortable: true,
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
                cell: (row: SkillType) => <span key={row.id + '-' + (Math.random() + 1).toString(36).substring(7)}>{row.xp}/{row.xp_max}</span>
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
                            <DangerButton button_label={'Stop Training'} on_click={() => this.stopTraining(row.id)} />
                        :
                            <PrimaryButton button_label={'Train'} on_click={() => this.trainSkill(row.id)} />
                    }
                </span>
            },
        ]
    }

    render() {
        return(
            <Table columns={this.buildColumns()} data={this.props.trainable_skills} dark_table={true} />
        )
    }

}