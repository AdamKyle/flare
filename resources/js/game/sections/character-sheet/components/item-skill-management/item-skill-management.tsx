import React, { Fragment } from "react";
import ItemSkillManagementProps from "./types/item-skill-management-props";
import ItemSkillTree from "./item-skill-tree";
import ItemSkillManagementState from "./types/item-skill-management-state";
import ItemSkillProgression from "./types/deffinitions/item-skill-progression";
import ItemSkillDetails from "./item-skill-details";
import DangerButton from "../../../../components/ui/buttons/danger-button";
import ItemSkill from "./types/deffinitions/item-skill";
import { isSkillLocked } from "./helpers/is-skill-locked";

export default class ItemSkillManagement extends React.Component<ItemSkillManagementProps, ItemSkillManagementState> {

    constructor(props: ItemSkillManagementProps) {
        super(props);

        this.state = {
            skill_progression: null,
            skill: null,
        }
    }

    showSkillSection(skill: ItemSkill | null, progression: ItemSkillProgression | null) {
        this.setState({
            skill_progression: progression,
            skill: skill,
        });
    }

    componentDidUpdate(prevProps: Readonly<ItemSkillManagementProps>): void {
        if (this.state.skill_progression !== null) {
            const updatedSkillProgressionInfo = this.props.skill_progression_data.find((data: ItemSkillProgression) => {
                return data.id === this.state.skill_progression?.id;
            });

            if (typeof updatedSkillProgressionInfo === 'undefined') {
                return;
            }

            if (updatedSkillProgressionInfo !== this.state.skill_progression) {
                this.setState({
                    skill_progression: updatedSkillProgressionInfo
                });
            }
        }
    }

    render() {

        if (this.state.skill_progression !== null && this.state.skill !== null) {
            return <ItemSkillDetails skill_progression_data={this.state.skill_progression}
                                     skills={this.props.skill_data}
                                     manage_skill_details={this.showSkillSection.bind(this)}
                                     character_id={this.props.character_id}
                                     is_skill_locked={isSkillLocked(this.state.skill, this.props.skill_data, this.props.skill_progression_data)}
                    />
        }

        return (
            <Fragment>
                <div className='p-4 text-center font-thin text-xl'>
                    <h3>Ancestral Skill Tree</h3>
                </div>
                <p className='text-center font-thin text-sm text-gray-600 dark:text-gray-300 italic mt-1'>
                    All the skills here will stack together. For more info please refer to: <a href='/information/ancestral-items' target='_blank' >
                    Ancestral items help docs <i
                        className="fas fa-external-link-alt"></i>
                    </a>.
                </p>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <DangerButton button_label={'Close Skill Tree'} on_click={() => this.props.close_skill_tree()} additional_css="mb-4"/>

                <ItemSkillTree
                    skill_data={this.props.skill_data}
                    progression_data={this.props.skill_progression_data}
                    show_skill_management={this.showSkillSection.bind(this)}
                />
            </Fragment>
        );
    }
}
