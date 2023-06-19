import React, { Fragment } from "react";
import ItemSkillManagementProps from "./types/item-skill-management-props";
import ItemSkillTree from "./item-skill-tree";
import ItemSkillManagementState from "./types/item-skill-management-state";
import ItemSkillProgression from "./types/deffinitions/item-skill-progression";
import ItemSkillDetails from "./item-skill-details";
import DangerButton from "../../../../components/ui/buttons/danger-button";

export default class ItemSkillManagement extends React.Component<ItemSkillManagementProps, ItemSkillManagementState> {

    constructor(props: ItemSkillManagementProps) {
        super(props);

        this.state = {
            skill_progression: null
        }
    }

    showSkillSectionl(skill: ItemSkillProgression | null) {
        this.setState({
            skill_progression: skill
        });
    }

    render() {

        if (this.state.skill_progression !== null) {
            return <ItemSkillDetails skill_progression_data={this.state.skill_progression} manage_skill_details={this.showSkillSectionl.bind(this)} />
        }

        return (
            <Fragment>
                <div className='p-4 text-center font-thin text-xl'>
                    <h3>Ancestral Skill Tree</h3>
                </div>
                <p className='text-center font-thin text-sm text-gray-600 dark:text-gray-300 italic mt-1'>
                    All the skills here will stack together. For more info please refer to: <a href='/information/item-skills' target='_blank' >
                        Item Skills help docs <i
                        className="fas fa-external-link-alt"></i>
                    </a>.
                </p>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <DangerButton button_label={'Close Skill Tree'} on_click={() => this.props.close_skill_tree()} additional_css="mb-4"/>

                <ItemSkillTree 
                    skill_data={this.props.skill_data} 
                    progression_data={this.props.skill_progression_data} 
                    show_skill_management={this.showSkillSectionl.bind(this)} 
                />
            </Fragment>
        );
    }
}