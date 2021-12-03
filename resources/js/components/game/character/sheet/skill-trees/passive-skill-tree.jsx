import React from 'react';
import { Tree, TreeNode } from 'react-organizational-chart';
import SkillNode from "./partials/skill-node";

export default class PassiveSkillTree extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      timerIsRunning: false,
    }
  }

  buildNodes(passiveSkill) {
    const nodes = [];

    if (passiveSkill.children.length > 0) {
      for (const child of passiveSkill.children) {
        nodes.push(
          <TreeNode label={<SkillNode
            passive={child}
            characterId={this.props.characterId}
            managePassiveTrainingModal={this.props.managePassiveTrainingModal}
            cancelPassiveTrain={this.props.cancelPassiveTrain}
            updateTimer={this.updateTimer.bind(this)}
            isTimerRunning={this.state.timerIsRunning}
          />}>
            {this.buildNodes(child)}
          </TreeNode>
        )
      }
    }

    return nodes;
  }

  updateTimer(shouldRun) {
    this.setState({
      timerIsRunning: shouldRun
    });
  }


  render() {
    return (
      <Tree
        lineWidth={'2px'}
        lineColor={'green'}
        lineBorderRadius={'10px'}
        label={<SkillNode
          passive={this.props.passiveSkill}
          characterId={this.props.characterId}
          managePassiveTrainingModal={this.props.managePassiveTrainingModal}
          cancelPassiveTrain={this.props.cancelPassiveTrain}
          updateTimer={this.updateTimer.bind(this)}
          isTimerRunning={this.state.timerIsRunning}
        />}
      >
        {this.buildNodes(this.props.passiveSkill)}
      </Tree>
    )
  }
}