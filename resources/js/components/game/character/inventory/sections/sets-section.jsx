import React from "react";
import {Tabs, Tab, Card} from "react-bootstrap";
import SetTabSection from "./set-tab-section";

export default class SetsSection extends React.Component {

  constructor(props) {
    super(props);
  }

  getClassName(set) {
    if (!set.can_be_equipped) {
      return 'set-stash'
    }

    if (set.is_equipped) {
      return 'set-equipped'
    }

    return '';
  }

  getTitle(index, equipped, canBeEquipped) {
    return (
      <span>
        Set {index + 1} {
            equipped ?
              <i className="ra ra-knight-helmet inventory-set-equipped"></i>
            : !canBeEquipped ?
                <i className="fas fa-exclamation-triangle inventory-set-error"></i>
            : null
        }
      </span>
    )
  }

  renderEachTab() {
    return this.props.sets.map((s, index) =>
      <Tab
        eventKey={s.id}
        title={this.getTitle(index, s.is_equipped, s.can_be_equipped)}
        tabClassName={this.getClassName(s)}
      >
        <SetTabSection
          set={s}
          characterId={this.props.characterId}
        />
      </Tab>)
  }

  render() {
    const activeSet = this.props.sets[0].id;

    return (
      <Card>
        <Card.Body>
          <Tabs defaultActiveKey={activeSet} id="set-section">
            {this.renderEachTab()}
          </Tabs>
        </Card.Body>
      </Card>
    );
  }
}