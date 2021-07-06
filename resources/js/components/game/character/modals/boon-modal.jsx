import React from 'react';
import {Modal, Button} from 'react-bootstrap';

export default class BoonModal extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      loading: false,
      message: null,
    }
  }

  componentDidUpdate() {
    const found = this.props.characterboons.filter((cb) => cb.id === this.props.boon.id);

    if (found.length === 0) {
      this.setState({
        loading: false,
        message: null,
      }, () => {
        this.props.close();
      });
    }
  }

  cancel() {

  }

  render() {
    return (
      <Modal
        show={this.props.show}
        onHide={this.props.close}
        aria-labelledby="character-boons"
        backdrop="static"
      >
        <Modal.Header closeButton>
          <Modal.Title id="character-boons">
            Boon Cancellation/Information
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <div className="alert alert-warning mt-2 mb-3">
            <p>By canceling this boon, your stats will adjust accordingly both here and in the game tab.</p>
          </div>

          {
            this.props.boon.type === 'Stat increase' ?
              <>
                <p>Increases all core character stats by: <span className="text-success">+<strong>{this.props.boon.stat_bonus * 100}</strong>%</span></p>
              </>
            : null
          }
          {
            this.props.boon.type === 'Effects skill' ?
              <>
                <dl>
                  <dt>Skills affected</dt>
                  <dd>{this.props.boon.affected_skills}</dd>
                  {
                    this.props.boon.affected_skill_base_ac_mod_bonus !== null ?
                      <>
                        <dt>Skill Base AC Mod</dt>
                        <dd className="text-success">+{this.props.boon.affected_skill_base_ac_mod_bonus * 100} %</dd>
                      </>
                    : null
                  }
                  {
                    this.props.boon.affected_skill_base_damage_mod_bonus !== null ?
                      <>
                        <dt>Skill Base Damge Mod</dt>
                        <dd className="text-success">+{this.props.boon.affected_skill_base_damage_mod_bonus * 100} %</dd>
                      </>
                      : null
                  }
                  {
                    this.props.boon.affected_skill_base_healing_mod_bonus !== null ?
                      <>
                        <dt>Skill Base Healing Mod</dt>
                        <dd className="text-success">+{this.props.boon.affected_skill_base_healing_mod_bonus * 100} %</dd>
                      </>
                      : null
                  }
                  {
                    this.props.boon.affected_skill_bonus !== null ?
                      <>
                        <dt>Skill Bonus</dt>
                        <dd className="text-success">+{this.props.boon.affected_skill_bonus * 100} %</dd>
                      </>
                      : null
                  }
                  {
                    this.props.boon.affected_skill_fight_time_out_mod_bonus !== null ?
                      <>
                        <dt>Skill Fight Time Out Bonus</dt>
                        <dd className="text-success">+{this.props.boon.affected_skill_fight_time_out_mod_bonus * 100} %</dd>
                      </>
                      : null
                  }
                  {
                    this.props.boon.affected_skill_move_time_out_mod_bonus !== null ?
                      <>
                        <dt>Skill Move Time Out Bonus</dt>
                        <dd className="text-success">+{this.props.boon.affected_skill_move_time_out_mod_bonus * 100} %</dd>
                      </>
                      : null
                  }
                  {
                    this.props.boon.affected_skill_training_bonus !== null ?
                      <>
                        <dt>Skill XP Bonus</dt>
                        <dd className="text-success">+{this.props.boon.affected_skill_training_bonus * 100} %</dd>
                      </>
                      : null
                  }
                </dl>
              </>
              : null
          }

          <Modal.Footer>
            <Button variant="danger" onClick={this.props.close}>
              Close
            </Button>
            <Button variant="success"  onClick={this.cancel.bind(this)}>
              Cancel Boon
            </Button>
          </Modal.Footer>
        </Modal.Body>
      </Modal>
    );
  }
}
