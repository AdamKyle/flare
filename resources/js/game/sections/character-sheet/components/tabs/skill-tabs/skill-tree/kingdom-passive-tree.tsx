import React, { Fragment } from "react";
import Node from "./node";
import { Tree, TreeNode } from "react-organizational-chart";
import TrainPassive from "../../../modals/skill-tree/train-passive";

export default class KingdomPassiveTree extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            show_training_modal: false,
            skill: null,
        };
    }

    buildNodes(passive: any) {
        let nodes: [] = [];

        if (passive.children.length > 0) {
            nodes = passive.children.map((child: any) => {
                return (
                    <TreeNode
                        label={
                            <Node
                                passive={child}
                                show_passive_modal={this.showTrainingModal.bind(
                                    this,
                                )}
                                is_automation_running={
                                    this.props.is_automation_running
                                }
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

    showTrainingModal(skill?: any) {
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
                            show_passive_modal={this.showTrainingModal.bind(
                                this,
                            )}
                            is_automation_running={
                                this.props.is_automation_running
                            }
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
                    />
                ) : null}
            </div>
        );
    }
}
