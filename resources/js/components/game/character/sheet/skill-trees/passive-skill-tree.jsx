import React from 'react';
import { Tree, TreeNode } from 'react-organizational-chart';
import SkillNode from "./partials/skill-node";

export default class PassiveSkillTree extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      timerIsRunning: false,
      passiveSkillWithChildren: this.props.passiveSkill,
    }

    this.passiveSkillTree = Echo.private('update-passive-skills-' + this.props.userId);
  }

  componentDidMount() {
    this.passiveSkillTree.listen('Game.PassiveSkills.Events.UpdatePassiveTree', (event) => {
      this.setState({
        passiveSkillWithChildren: event.passiveSkills[0]
      });
    });
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
            isDead={this.props.isDead}
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
          passive={this.state.passiveSkillWithChildren}
          characterId={this.props.characterId}
          managePassiveTrainingModal={this.props.managePassiveTrainingModal}
          cancelPassiveTrain={this.props.cancelPassiveTrain}
          updateTimer={this.updateTimer.bind(this)}
          isTimerRunning={this.state.timerIsRunning}
          isDead={this.props.isDead}
        />}
      >
        {this.buildNodes(this.state.passiveSkillWithChildren)}
      </Tree>
    )
  }
}
