import React from 'react';
import {Modal, Button} from 'react-bootstrap';
import LoadingModal from "../../components/loading/loading-modal";
import AttackType from "../../battle/attack/attack-type";

export default class AdventureEmbark extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      adventure: null,
      isLoading: true,
      attackType: AttackType.ATTACK,
    }
  }

  componentDidMount() {
    this.setState({
      adventure: this.props.adventure,
      isLoading: false,
    });
  }

  embark() {
    axios.post('/api/character/' + this.props.characterId + '/adventure/' + this.state.adventure.id, {
      attack_type: this.state.attackType,
    }).then((result) => {
      this.props.updateMessage(result.data.message);
      this.props.updateCharacterAdventures(result.data.adventure_completed_at);
      this.props.embarkClose();
    }).catch((error) => {
      this.props.embarkClose();

      if (error.hasOwnProperty('response')) {
        const response = error.response;

        if (response.status === 401) {
          return location.reload();
        }

        if (response.status === 429) {
          return this.props.openTimeOutModal()
        }
      }

      this.setState({
        errorMessage: 'Invalid input. Please try again.'
      });
    });
  }

  selectAttackType(event) {
    this.setState({
      attackType: event.target.value
    });
  }

  render() {
    if (this.state.isLoading) {
      return (
        <LoadingModal
          show={this.props.show}
          close={this.props.embarkClose}
          loadingText={"One moment ..."}
        />
      );
    }

    return (
      <>
        <Modal show={this.props.show} onHide={this.props.embarkClose}>
          <Modal.Header closeButton>
            <Modal.Title>{this.state.adventure.name}</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <p>
              {this.state.adventure.description}
            </p>
            <hr />
            <h4 className="alert-heading">Before you set off.</h4>
            <p>
              Please make sure you have equipped any items you want for this adventure. <br/>
              Once started you cannot do the following:
            </p>
            <ul className="mb-1 mt-2">
              <li>Craft/Enchant</li>
              <li>Move/Set Sail or Teleport</li>
              <li>Manage Equipment (Including equip, unequip or destroy)</li>
              <li>Fight Monsters</li>
              <li>Manage/Attack or Settle Kingdoms</li>
              <li>Train Skills</li>
              <li>Buy Items (From Shop or Market)</li>
              <li>List or Sell Items (From Shop or Market)</li>
            </ul>
            <hr/>
            <p className="mb-0">Should you need additional help, please consider this resource on <a
              href="/information/adventure" target="_blank">adventureing</a>.</p>
            <p className="text-muted" style={{fontSize: '12px'}}> You are free to logout. Any relevant details will be
              emailed to you should you have those settings enabled.</p>
            <div className="mt-2">
              <span className="text-muted"><strong>Total Levels</strong>: {this.state.adventure.levels}</span>
              <br/>
              <span
                className="text-muted"><strong>Total Time</strong>: {this.state.adventure.levels * this.state.adventure.time_per_level} minutes</span>
            </div>
            <hr />
            <div className="form-group">
              <label htmlFor="attack-type">Please select an Attack Type</label>
              <select className="form-control" id="attack-type" value={this.state.attackType} onChange={this.selectAttackType.bind(this)}>
                <option value={AttackType.ATTACK}>Attack</option>
                <option value={AttackType.CAST}>Cast</option>
                <option value={AttackType.CAST_AND_ATTACK}>Cast then Attack</option>
                <option value={AttackType.ATTACK_AND_CAST}>Attack then Cast</option>
                <option value={AttackType.DEFEND}>Defend</option>
              </select>
            </div>
            <p>Each attack type corresponds to the attack button from drop down critters. This attack type will be used
            for all floors in the adventure. Choose carefully, you cannot change it once you embark.</p>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" onClick={this.props.embarkClose}>
              Cancel
            </Button>
            <Button variant="primary" onClick={this.embark.bind(this)}>
              Embark
            </Button>
          </Modal.Footer>
        </Modal>
      </>
    );
  }
}
