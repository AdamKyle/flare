import React from "react";
import ItemSkillTreeProps from "./types/item-skill-tree-props";
import { Tree, TreeNode } from "react-organizational-chart";
import ItemSkill from './types/deffinitions/item-skill';
import SkillTreeNode from "./skill-tree/skill-tree-node";
import ItemSkillProgression from "./types/deffinitions/item-skill-progression";
import DangerAlert from "../../../../components/ui/alerts/simple-alerts/danger-alert";

export default class ItemSkillTree extends React.Component<ItemSkillTreeProps, any> {

    constructor(props: ItemSkillTreeProps) {
        super(props)
    }

    buildNodes(skills: ItemSkill): (JSX.Element | undefined)[] {
        let nodes: (JSX.Element | undefined)[];

        nodes = skills.children.map((child: ItemSkill) => {

            const progressionData = this.getSkillProgressionData(child);

            if (typeof progressionData !== 'undefined')  {

                return (
                    <TreeNode label={<SkillTreeNode 
                        skill={child}
                        skill_progression={progressionData}
                        show_passive_modal={this.props.show_skill_management}
                        is_locked={this.isSkillLocked(child)}
                    />}>
                        {this.buildNodes(child)}
                    </TreeNode>
                );
            }
        })

        return nodes.filter((item: JSX.Element | undefined) => {
            return typeof item !== 'undefined';
        });
    }

    getSkillProgressionData(skill: ItemSkill): ItemSkillProgression | undefined {

        return this.props.progression_data.find((data: ItemSkillProgression) => data.item_skill_id === skill.id);
    }

    isSkillLocked(child: ItemSkill): boolean {

        const parentSkill: ItemSkill | undefined = this.findParentSkill(child, this.props.skill_data);
        let isLocked      = false;
        
        if (typeof parentSkill !== 'undefined') {
            const progressionDataForParent = this.getSkillProgressionData(parentSkill);
            if (typeof progressionDataForParent !== 'undefined' && child.parent_level_needed !== null) {
                isLocked = progressionDataForParent.current_level <= child.parent_level_needed;
            }
        }

        return isLocked;
    }

    findParentSkill(child: ItemSkill, skills: ItemSkill[]): ItemSkill | undefined {
        for (const skillData of skills) {

            if (skillData.id === child.parent_id) {
                return skillData;
            }

            if (skillData.children.length > 0) {
                const parentSkill: ItemSkill | undefined = this.findParentSkill(child, skillData.children);

                if (typeof parentSkill !== 'undefined') {
                    return parentSkill
                }
            }
        }

        return undefined;
    }

    render() {

        const progressionData = this.getSkillProgressionData(this.props.skill_data[0]);

        if (typeof progressionData === 'undefined') {
            return (
                <DangerAlert>
                    Could not render item skill tree, something is wrong. The First skill does not have any progression data.
                </DangerAlert>
            )
        }
        
        return (
            <div className='overflow-x-auto overflow-y-hidden max-w-[300px] sm:max-w-[600px] md:max-w-[100%]'>
                <Tree
                    lineWidth={'2px'}
                    lineColor={'green'}
                    lineBorderRadius={'10px'}
                    label={<SkillTreeNode 
                        skill={this.props.skill_data[0]}
                        skill_progression={progressionData}
                        show_passive_modal={this.props.show_skill_management}
                        is_locked={false}
                    />}
                >
                    {this.buildNodes(this.props.skill_data[0])}
                </Tree>
            </div>
        )
    }
}