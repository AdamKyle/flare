import React, {Fragment} from "react";
import Table from "../../../../../components/ui/data-tables/table";
import SkillType from "../../../../../lib/game/character-sheet/types/skills/skill-type";
import CraftingSkillsProps from "../../../../../lib/game/character-sheet/types/skills/tables/crafting-skills-props";
import SkillInformation from "../../modals/skills/skill-information";
import InfoAlert from "../../../../../components/ui/alerts/simple-alerts/info-alert";

export default class CraftingSkills extends React.Component<CraftingSkillsProps, any> {

    constructor(props: CraftingSkillsProps) {
        super(props);

        this.state = {
            show_skill_details: false,
            skill: null,
        }
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
                cell: (row: SkillType) => <span key={row.id + '-' + (Math.random() + 1).toString(36).substring(7)}>{row.xp}/{row.xp_max}</span>
            },
        ]
    }

    render() {
        return(
            <Fragment>
                <div className='mb-4'>
                    <InfoAlert>
                        This section will not update in real time.
                    </InfoAlert>
                </div>

                <Table columns={this.buildColumns()} data={this.props.crafting_skills} dark_table={this.props.dark_table} />

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
