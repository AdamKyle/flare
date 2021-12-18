import React from 'react';
import {Tree, TreeNode} from "react-organizational-chart";
import QuestNode from "./quest-node";
import SkillNode from "../../character/sheet/skill-trees/partials/skill-node";

export default class QuestTree extends React.Component {

  constructor(props) {
    super(props);
  }

  buildNodes(parentQuest) {
    const nodes = [];

    if (parentQuest.child_quests.length > 0) {
      for (const child of parentQuest.child_quests) {
        nodes.push(
          <TreeNode label={<QuestNode quest={child} />}>
            {this.buildNodes(child)}
          </TreeNode>
        )
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
        label={<QuestNode quest={this.props.parentQuest} />}
      >
        {this.buildNodes(this.props.parentQuest)}
      </Tree>
    );
  }
}