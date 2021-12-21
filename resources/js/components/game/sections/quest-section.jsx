import React, {Fragment} from 'react';
import Card from "../components/templates/card";
import {Tab, Tabs} from "react-bootstrap";
import QuestTree from "./trees/quest-tree";
import AlertInfo from "../components/base/alert-info";
import QuestTreeOverView from "./modals/quest-tree-over-view";

export default class QuestSection extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      loading: true,
      showQuestTree: false,
      npcs: [],
      firstTab: null,
      completedQuests: [],
      allQuests: [],
    }
  }

  componentDidMount() {
    axios.get('/api/map/quests/' + this.props.characterId).then((result) => {
      this.setState({
        loading: false,
        npcs: result.data.npcs,
        completedQuests: result.data.completed_quests,
        firstTab: result.data.npcs.length > 0 ? result.data.npcs[0].name : null,
        allQuests: result.data.all_quests,
      });
    }).catch((err) => {
      if (err.hasOwnProperty('response')) {
        const response = err.response;

        if (response.status === 401) {
          return location.reload();
        }

        if (response.status === 429) {
          return this.props.openTimeOutModal()
        }
      }
    });
  }

  hideQuests() {
    this.props.openQuestDetails(false)
  }

  renderParentQuestTrees(quests) {
    return quests.map((quest) => {
      return <>
        <QuestTree parentQuest={quest} key={quest.id} completedQuests={this.state.completedQuests}/>
        {quests.length > 0 ? <hr /> : null}
      </>
    });
  }

  renderTabs() {
    return this.state.npcs.map((npc) => {
      return <Tab eventKey={npc.name} title={npc.real_name}>
        <div className="mt-3">
          {this.renderParentQuestTrees(npc.quests.filter((quest) => quest.is_parent))}
        </div>
      </Tab>
    });
  }

  manageQuestTree() {
    this.setState({
      showQuestTree: !this.state.showQuestTree
    })
  }

  render() {
    return (
      <Card
        OtherCss="p-3"
        cardTitle="Quests"
        close={this.hideQuests.bind(this)}
        additionalButton={
          <button className="float-right btn btn-primary btn-sm mr-2" onClick={this.manageQuestTree.bind(this)} disabled={this.state.npcs.length === 0}>
            All quests
          </button>
        }
      >
        {
          this.state.loading ?
            <div className="progress loading-progress mt-2 mb-2" style={{position: 'relative'}}>
              <div className="progress-bar progress-bar-striped indeterminate">
              </div>
            </div>
            :
            this.state.npcs.length === 0 ?
              <Fragment>
                <div className="text-center">There are no quests for this plane.</div>
              </Fragment>
              :
              <Fragment>
                <AlertInfo icon={"fas fa-question-circle"} title={"ATTN!"}>
                  <p>
                    <strong>
                      This tree will not update in real time. It is designed as a reference. Opening and closing will update the quest tree.
                    </strong>
                  </p>
                  <p>
                    Quests with lines separating them running horizontally are individual quests that can be done in any order.
                  </p>
                  <p>
                    Quests with blue lines separating them are quests that should be, or have to be, done in order. You see the parent skill
                    is unlocked, and the children skills will be locked. As you complete quests, child quests will open up and the completed
                    quests will be colored green with a checkmark beside them.
                  </p>
                  <p>
                    Once a quest is complete, the one below it, assuming it's a child quest, will open up - all you have to do is meet the requirements and speak to the NPC
                    with the right command and that's it.
                  </p>
                  <p>
                    Clicking the name of any quest will show you all the relevant details you need to complete that quest,
                    including: locations, monsters to fight, what plane, how to get to said plane, adventures, faction point levels needed,
                    items and where to get them.
                  </p>
                  <p>
                    You wil also be shown what NPC and where and how to get to them on the plane you are on. You wil also be shown a list of rewards
                    for completing said quest.
                  </p>
                </AlertInfo>
                <Tabs defaultActiveKey={this.state.firstTab} id="map-quests">
                  {this.renderTabs()}
                </Tabs>
              </Fragment>
        }
        {
          this.state.showQuestTree ?
            <Fragment>
              <QuestTreeOverView
                allQuests={this.state.allQuests}
                completedQuests={this.state.completedQuests}
                show={this.state.showQuestTree}
                close={this.manageQuestTree.bind(this)}
              />
            </Fragment>
          : null
        }
      </Card>
    );
  }
}
