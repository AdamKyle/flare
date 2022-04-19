import React from 'react';
import {Modal, Button, Tabs, Tab} from 'react-bootstrap';
import QuestTree from "../trees/quest-tree";
import {groupBy, isEmpty} from "lodash";

export default class QuestTreeOverView extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      mapSpecificQuests: [],
    }
  }

  componentDidMount() {
    this.setState({
      mapSpecificQuests: groupBy(this.props.allQuests, 'belongs_to_map_name'),
    })
  }

  renderQuestTrees() {
    const keys = Object.keys(this.state.mapSpecificQuests);

    if (keys.length === 0) {
      return null;
    }

    return keys.map((key) => {

      return <Tab eventKey={key} title={key}>
        <div className="mt-4">
          {this.renderTrees(key)}
        </div>
      </Tab>
    });
  }

  renderTrees(key) {

    const childQuests = [];
    const singleQuests = [];

    for (let i = 0; i < this.state.mapSpecificQuests[key].length; i++) {
      const quest = this.state.mapSpecificQuests[key][i];

      if (quest.is_parent && isEmpty(quest.child_quests)) {
        singleQuests.push(
          <QuestTree parentQuest={quest} completedQuests={this.props.completedQuests} ignoreNpcCheck={true} />
        )
      } else {
        childQuests.push(
          <QuestTree parentQuest={quest} completedQuests={this.props.completedQuests} ignoreNpcCheck={true} />
        )
      }
    }

    return (
      <div className="row">
        {
          childQuests.length === 0 &&  singleQuests.length > 0 ?
            <div className='col-md-12'>
              <h4 className="tw-font-light">One off quests</h4>
              <hr />
              {singleQuests}
            </div>
            : null
        }
        {
          childQuests.length > 0 &&  singleQuests.length > 0 ?
            <div className='col-md-2'>
              <h4 className="tw-font-light">One off quests</h4>
              <hr />
              {singleQuests}
            </div>
            : null
        }

        {
          singleQuests.length === 0 &&  childQuests.length > 0 ?
            <div className='col-md-12'>
              <h4 className="tw-font-light">Quest chain</h4>
              <hr />
              {childQuests}
            </div>
            : null
        }

        {
          singleQuests.length > 0 &&  childQuests.length > 0 ?
            <div className='col-md-10'>
              <h4 className="tw-font-light">Quest chain</h4>
              <hr />
              {childQuests}
            </div>
            : null
        }
      </div>
    )
  }

  renderAllQuestsWithChildren() {
    const surfaceWithQuests = this.props.allQuests.filter((aq) => aq.belongs_to_map_name === 'Surface' && aq.child_quests.length > 0)[0];

    return <QuestTree parentQuest={surfaceWithQuests} completedQuests={this.props.completedQuests} ignoreNpcCheck={true} />
  }

  renderAllQuestsWitOutChildren() {
    const childLessQuests = this.props.allQuests.filter((aq) => aq.child_quests.length === 0);

    return childLessQuests.map((cq) => {
      return <QuestTree parentQuest={cq} completedQuests={this.props.completedQuests} ignoreNpcCheck={true} />
    });
  }

  render() {
    return (
      <>
        <Modal show={this.props.show} onHide={this.props.close} dialogClassName="large-modal">
          <Modal.Header closeButton>
            <Modal.Title>All Quests</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <p>
              Below you will find a tree for each parent quest. This gives you an idea of how all the quests are linked together.
              You can click on each quest to view its relevant details.
            </p>
            <p>
              Some quests, specifically those in chains (blue lines connecting) can take you across multiple planes. Make sure you have the appropriate
              access. All quests are broken down by the quest parents starting plane.
            </p>
            <hr />
            <Tabs defaultActiveKey="Surface" id="all-quests">
              {this.renderQuestTrees()}
            </Tabs>

          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" onClick={this.props.close}>
              Close
            </Button>
          </Modal.Footer>
        </Modal>
      </>
    );
  }
}
