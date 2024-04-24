import React from "react";
import ItemSkillTreeProps from "./types/item-skill-tree-props";
import { Tree, TreeNode } from "react-organizational-chart";
import ItemSkill from "./types/deffinitions/item-skill";
import SkillTreeNode from "./skill-tree/skill-tree-node";
import DangerAlert from "../../../../components/ui/alerts/simple-alerts/danger-alert";
import {
    isSkillLocked,
    getSkillProgressionData,
} from "./helpers/is-skill-locked";

export default class ItemSkillTree extends React.Component<
    ItemSkillTreeProps,
    any
> {
    constructor(props: ItemSkillTreeProps) {
        super(props);
    }

    buildNodes(skills: ItemSkill): (JSX.Element | undefined)[] {
        let nodes: (JSX.Element | undefined)[];

        nodes = skills.children.map((child: ItemSkill) => {
            const progressionData = getSkillProgressionData(
                child,
                this.props.progression_data,
            );

            if (typeof progressionData !== "undefined") {
                return (
                    <TreeNode
                        label={
                            <SkillTreeNode
                                skill={child}
                                skill_progression={progressionData}
                                show_passive_modal={
                                    this.props.show_skill_management
                                }
                                is_locked={isSkillLocked(
                                    child,
                                    this.props.skill_data,
                                    this.props.progression_data,
                                )}
                            />
                        }
                    >
                        {this.buildNodes(child)}
                    </TreeNode>
                );
            }
        });

        return nodes.filter((item: JSX.Element | undefined) => {
            return typeof item !== "undefined";
        });
    }

    render() {
        const progressionData = getSkillProgressionData(
            this.props.skill_data[0],
            this.props.progression_data,
        );

        if (typeof progressionData === "undefined") {
            return (
                <DangerAlert>
                    Could not render item skill tree, something is wrong. The
                    First skill does not have any progression data.
                </DangerAlert>
            );
        }

        return (
            <div className="overflow-x-auto overflow-y-hidden max-w-[300px] sm:max-w-[600px] md:max-w-[100%]">
                <Tree
                    lineWidth={"2px"}
                    lineColor={"green"}
                    lineBorderRadius={"10px"}
                    label={
                        <SkillTreeNode
                            skill={this.props.skill_data[0]}
                            skill_progression={progressionData}
                            show_passive_modal={
                                this.props.show_skill_management
                            }
                            is_locked={false}
                        />
                    }
                >
                    {this.buildNodes(this.props.skill_data[0])}
                </Tree>
            </div>
        );
    }
}
