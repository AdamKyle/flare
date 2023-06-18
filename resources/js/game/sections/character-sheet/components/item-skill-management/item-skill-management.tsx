import React from "react";
import ItemSkillManagementProps from "./types/item-skill-management-props";
import ItemSkillTree from "./item-skill-tree";
import ItemSkillManagementState from "./types/item-skill-management-state";
import ItemSkillProgression from "./types/deffinitions/item-skill-progression";
import ItemSkillDetails from "./item-skill-details";

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
            return <ItemSkillDetails skill_progression_data={this.state.skill_progression} />
        }

        return <ItemSkillTree skill_data={this.props.skill_data} progression_data={this.props.skill_progression_data} show_skill_management={this.showSkillSectionl.bind(this)} />
    }
}