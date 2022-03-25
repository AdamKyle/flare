import React from "react";
import Table from "../../../../../components/ui/data-tables/table";
import SkillType from "../../../../../lib/game/character-sheet/types/skills/skill-type";
import DangerButton from "../../../../../components/ui/buttons/danger-button";
import PrimaryButton from "../../../../../components/ui/buttons/primary-button";
import CraftingSkillsProps from "../../../../../lib/game/character-sheet/types/skills/tables/crafting-skills-props";

export default class CraftingSkills extends React.Component<CraftingSkillsProps, any> {

    constructor(props: CraftingSkillsProps) {
        super(props);
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
        ]
    }

    render() {
        return(
            <Table columns={this.buildColumns()} data={this.props.crafting_skills} dark_table={true} />
        )
    }

}
