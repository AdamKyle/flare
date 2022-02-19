import React, {Fragment} from 'react';
import Card from "../components/templates/card";
import QuestTree from "./trees/quest-tree";
import AlertInfo from "../components/base/alert-info";
import QuestTreeOverView from "./modals/quest-tree-over-view";
import localforage from "localforage";
import {groupBy, isEmpty} from "lodash";

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
      map_name: null,
    }
  }

  componentDidMount() {

    localforage.getItem('all-quests').then((value) => {
      if (value === null) {
        this.fetchQuests(0);
      } else {
        this.setState({
          allQuests: value,
        }, () => {
          this.fetchQuests(1);
        })
      }
    }).catch((error) => {
      console.error(error);
    });
  }

  fetchQuests(fetchCompletedTasksOnly) {
    axios.get('/api/map/quests/' + this.props.characterId, {
      params: {completed_quests_only: fetchCompletedTasksOnly}
    }).then((result) => {
      this.setState({
        loading: false,
        completedQuests: result.data.completed_quests,
        map_name: result.data.map_name,
        allQuests: result.data.hasOwnProperty('all_quests') ? result.data.all_quests : this.state.allQuests,
      }, () => {
        if (result.data.was_reset) {
          localforage.setItem('all-quests', result.data.all_quests).catch((err) => {
            console.error('could not save data to local forage for all quests');
            console.error(err);
          });
        }
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

  renderTrees(key) {
    const mapQuests = groupBy(this.state.allQuests, 'belongs_to_map_name');

    const childQuests = [];
    const singleQuests = [];

    if (!mapQuests.hasOwnProperty(key)) {
      return <div className="mt-3 text-center"> There are no quests that are parent or one offs for this plane.
        Other planes may have quest chains that come down here. Check the All Quests section.</div>
    }

    for (let i = 0; i < mapQuests[key].length; i++) {
      const quest = mapQuests[key][i];

      if (quest.is_parent && isEmpty(quest.child_quests)) {
        singleQuests.push(
          <QuestTree parentQuest={quest} completedQuests={this.state.completedQuests} ignoreNpcCheck={true} />
        )
      } else {
        childQuests.push(
          <QuestTree parentQuest={quest} completedQuests={this.state.completedQuests} ignoreNpcCheck={true} />
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
          <button className="float-right btn btn-primary btn-sm mr-2" onClick={this.manageQuestTree.bind(this)} disabled={this.state.loading}>
            All Quests
          </button>
        }
      >
        {
          this.state.loading ?
            <Fragment>
              <AlertInfo icon={'fas fa-question-circle'} title={"Caution"}>
                <p>
                  This can take a moment or two to load. We are currently fetching a lot of data. How ever in the future, when you close and re open this, we simply
                  fetch this data from the browser cache. Sometimes The Creator might update quests and you will see this message again.
                  <strong>This data is stored in YOUR browser cache between logins and contains no sensitive information about your account</strong>.
                </p>
              </AlertInfo>
              <div className="progress loading-progress mt-2 mb-2" style={{position: 'relative'}}>
                <div className="progress-bar progress-bar-striped indeterminate">
                </div>
              </div>
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
                  Here you can see all the quests for the plane you are currently on. Some planes do not have quests "on their own", as their quests are apart of a larger
                  quest line you can see while standing on surface.
                </p>
                <p>
                  Each quest will tell you EXACTLY where to go, what to do, where to get what you need, what you get, how to access the NPC if it's not on surface. Everything.
                  All you have to do is click on the quest name.
                </p>
                <p>
                  Clicking All Quests at the top will open a modal that shows you exactly what it states. Here you can see a massive break down of all the quests. Again clicking their name opens
                  an appropriate modal to show you everything you need to complete that quest.
                </p>
                <p>
                  Green quests are ones you have completed. If you complete a quest with this window open, close it and re-open to see it updated. Blue quests are quests you can do now.
                  While red quests are ones locked behind the completion of other quests. <strong>All major game features can be unlocked by the player simply completing these quests. A player cannot
                  use any real world money to progress faster.</strong>
                </p>
              </AlertInfo>
              {this.renderTrees(this.state.map_name)}
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
