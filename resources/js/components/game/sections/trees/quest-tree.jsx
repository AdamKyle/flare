import React from 'react';
import {Tree, TreeNode} from "react-organizational-chart";
import QuestNode from "./quest-node";

export default class QuestTree extends React.Component {

  constructor(props) {
    super(props);
  }

  buildNodes(parentQuest) {
    const nodes = [];

    if (parentQuest.child_quests.length > 0) {
      for (const child of parentQuest.child_quests) {
        if (parentQuest.npc.id === child.npc.id) {
          nodes.push(
            <TreeNode label={<QuestNode quest={child} parentNPCID={this.props.parentQuest.npc.id}/>}>
              {this.buildNodes(child)}
            </TreeNode>
          );
        }
      }
    }

    return nodes;
  }

  render() {
    return (
      <Tree
        lineWidth={'2px'}
        lineColor={'blue'}
        lineBorderRadius={'10px'}
        label={<QuestNode quest={this.props.parentQuest} parentNPCID={this.props.parentQuest.npc.id}/>}
      >
        {this.buildNodes(this.props.parentQuest)}
      </Tree>
    );
  }
}