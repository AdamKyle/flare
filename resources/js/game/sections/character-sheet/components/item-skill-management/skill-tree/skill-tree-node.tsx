import React from "react";
import clsx from "clsx";
import SkillTreeNodeProps from "../types/skill-tree/skill-tree-node-props";

export default class SkillTreeNode extends React.Component<SkillTreeNodeProps, {}> {

    constructor(props: SkillTreeNodeProps) {
        super(props);
    }

    isActive(): boolean {
        if (this.props.skill.parent_level_needed === null) {
            return true;
        }

        return !this.props.is_locked;
    }

    isMaxLevel(): boolean {
        return this.props.skill_progression.current_level === this.props.skill_progression.item_skill.max_level;
    }

    render() {
        return (
            <div>
                <button onClick={() => this.props.show_passive_modal(this.props.skill_progression)}>
                    <h4 className={clsx({
                        'text-red-500 dark:text-red-400' : this.props.is_locked,
                        'text-blue-500 dark:text-blue-400' : this.props.skill.parent_id === null,
                        'text-green-700 dark:text-green-600': this.isActive(),
                    })}>
                        {this.props.skill.name}
                    </h4>
                </button>
                <p className='mt-3'>Level: {this.props.skill_progression.current_level}/{this.props.skill_progression.item_skill.max_level}</p>
                {
                    !this.isMaxLevel() ?
                        <p className='mt-3'>Kills till next level: {this.props.skill_progression.current_kill}/{this.props.skill_progression.item_skill.total_kills_needed}</p>
                    :
                        <p className='text-green-700 dark:text-green-600 mt-3'>Skill is maxed out!</p>
                }
            </div>
        )
    }
}