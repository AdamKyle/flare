import React, { Fragment } from "react";
import Node from "./node";
import { Tree, TreeNode } from "react-organizational-chart";
import TrainPassive from "../../../modals/skill-tree/train-passive";
import KingdomPassiveRow from "../../../../../../lib/game/character-sheet/types/skills/kingdom-passive-row";

interface KingdomPassiveTreeProps {
    passives: KingdomPassiveRow;
    read_only?: boolean;
    manage_success_message?: (message: string) => void;
    update_passives?: (
        passives: KingdomPassiveRow[],
        passive?: KingdomPassiveRow,
    ) => void;
    character_id?: number;
    is_dead?: boolean;
    is_automation_running?: boolean;
    active_automation?: { name: string } | null;
    skill_in_training?: KingdomPassiveRow | null;
}

interface KingdomPassiveTreeState {
    show_training_modal: boolean;
    skill: KingdomPassiveRow | null;
}

export default class KingdomPassiveTree extends React.Component<
    KingdomPassiveTreeProps,
    KingdomPassiveTreeState
> {
    constructor(props: KingdomPassiveTreeProps) {
        super(props);

        this.state = {
            show_training_modal: false,
            skill: null,
        };
    }

    buildNodes(passive: KingdomPassiveRow): JSX.Element[] {
        let nodes: JSX.Element[] = [];

        if (passive.children.length > 0) {
            nodes = passive.children.map((child) => {
                return (
                    <TreeNode
                        label={
                            <Node
                                passive={child}
                                read_only={this.props.read_only}
                                show_passive_modal={this.showTrainingModal.bind(
                                    this,
                                )}
                            />
                        }
                    >
                        {this.buildNodes(child)}
                    </TreeNode>
                );
            });
        }

        return nodes;
    }

    showTrainingModal(skill?: KingdomPassiveRow) {
        this.setState({
            show_training_modal: !this.state.show_training_modal,
            skill: typeof skill === "undefined" ? null : skill,
        });
    }

    render() {
        return (
            <div className="overflow-x-auto overflow-y-hidden max-w-[300px] sm:max-w-[600px] md:max-w-[100%]">
                <Tree
                    lineWidth={"2px"}
                    lineColor={"green"}
                    lineBorderRadius={"10px"}
                    label={
                        <Node
                            passive={this.props.passives}
                            read_only={this.props.read_only}
                            show_passive_modal={this.showTrainingModal.bind(
                                this,
                            )}
                        />
                    }
                >
                    {this.buildNodes(this.props.passives)}
                </Tree>

                {this.state.show_training_modal && this.state.skill !== null ? (
                    <TrainPassive
                        is_open={this.state.show_training_modal}
                        manage_modal={this.showTrainingModal.bind(this)}
                        skill={this.state.skill}
                        manage_success_message={
                            this.props.manage_success_message
                        }
                        update_passives={this.props.update_passives}
                        character_id={this.props.character_id}
                        is_dead={this.props.is_dead}
                        is_automation_running={this.props.is_automation_running}
                        active_automation={this.props.active_automation}
                        skill_in_training={this.props.skill_in_training}
                        read_only={this.props.read_only}
                    />
                ) : null}
            </div>
        );
    }
}
