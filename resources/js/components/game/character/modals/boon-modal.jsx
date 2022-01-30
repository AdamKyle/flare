import React from 'react';
import {Modal, Button} from 'react-bootstrap';

export default class BoonModal extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      loading: false,
      message: null,
      canceling: false,
    }
  }

  componentDidUpdate() {
    const found = this.props.characterBoons.filter((cb) => cb.id === this.props.boon.id);

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
    this.setState({
      canceling: true,
    }, () => {
      axios.post('/api/character-sheet/'+this.props.boon.character_id+'/remove-boon/' + this.props.boon.id)
        .then((result) => {

          this.props.showSuccess(true);
          this.props.fetchBoons();

          this.setState({
            canceling: false
          }, () => {
            this.props.close();
          });
        })
        .catch((err) => {
          this.setState({
            canceling: false
          }, () => {
            console.error(err);

            if (response.status === 401 || response.status === 429) {
              return location.reload()
            }
          });
        });
    })

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
          <dl className="mb-2">
            {
              this.props.boon.stat_bonus > 0.0 ?
                <>
                  <dt>All Core Stat Modifier</dt>
                  <dd className="text-success">+{(this.props.boon.stat_bonus * 100).toFixed(2)}%</dd>
                </>
                : null
            }
            {
              this.props.boon.str_mod > 0.0 ?
                <>
                  <dt>Str Modifier</dt>
                  <dd className="text-success">+{(this.props.boon.str_mod * 100).toFixed(2)}%</dd>
                </>
                : null
            }
            {
              this.props.boon.dex_mod > 0.0 ?
                <>
                  <dt>Dex Modifier</dt>
                  <dd className="text-success">+{(this.props.boon.dex_mod * 100).toFixed(2)}%</dd>
                </>
                : null
            }
            {
              this.props.boon.dur_mod > 0.0 ?
                <>
                  <dt>Dur Modifier</dt>
                  <dd className="text-success">+{(this.props.boon.dur_mod * 100).toFixed(2)}%</dd>
                </>
                : null
            }
            {
              this.props.boon.int_mod > 0.0 ?
                <>
                  <dt>Int Modifier</dt>
                  <dd className="text-success">+{(this.props.boon.int_mod * 100).toFixed(2)}%</dd>
                </>
                : null
            }
            {
              this.props.boon.chr_mod > 0.0 ?
                <>
                  <dt>Chr Modifier</dt>
                  <dd className="text-success">+{(this.props.boon.chr_mod * 100).toFixed(2)}%</dd>
                </>
                : null
            }
            {
              this.props.boon.agi_mod > 0.0 ?
                <>
                  <dt>AGI Modifier</dt>
                  <dd className="text-success">+{(this.props.boon.agi_mod * 100).toFixed(2)}%</dd>
                </>
                : null
            }
            {
              this.props.boon.focus_mod > 0.0 ?
                <>
                  <dt>Focus Modifier</dt>
                  <dd className="text-success">+{(this.props.boon.focus_mod * 100).toFixed(2)}%</dd>
                </>
                : null
            }
          </dl>
          <hr />
          {
            this.props.boon.type === 'Effects skill' ?
              <div className="mb-2">
                <dl>
                  <dt>Skills affected</dt>
                  <dd>{this.props.boon.affected_skills}</dd>
                  {
                    this.props.boon.base_ac_mod_bonus !== null ?
                      <>
                        <dt>Skill Base AC Mod</dt>
                        <dd className="text-success">+{(this.props.boon.base_ac_mod_bonus * 100).toFixed(2)} %</dd>
                      </>
                    : null
                  }
                  {
                    this.props.boon.base_damage_mod_bonus !== null ?
                      <>
                        <dt>Skill Base Damge Mod</dt>
                        <dd className="text-success">+{(this.props.boon.base_damage_mod_bonus * 100).toFixed(2)} %</dd>
                      </>
                      : null
                  }
                  {
                    this.props.boon.base_healing_mod_bonus !== null ?
                      <>
                        <dt>Skill Base Healing Mod</dt>
                        <dd className="text-success">+{(this.props.boon.base_healing_mod_bonus * 100).toFixed(2)} %</dd>
                      </>
                      : null
                  }
                  {
                    this.props.boon.skill_bonus !== null ?
                      <>
                        <dt>Skill Bonus</dt>
                        <dd className="text-success">+{(this.props.boon.skill_bonus * 100).toFixed(2)} %</dd>
                      </>
                      : null
                  }
                  {
                    this.props.boon.fight_time_out_mod_bonus !== null ?
                      <>
                        <dt>Skill Fight Time Out Bonus</dt>
                        <dd className="text-success">+{(this.props.boon.fight_time_out_mod_bonus * 100).toFixed(2)} %</dd>
                      </>
                      : null
                  }
                  {
                    this.props.boon.skill_training_bonus !== null ?
                      <>
                        <dt>Skill XP Bonus</dt>
                        <dd className="text-success">+{(this.props.boon.skill_training_bonus * 100).toFixed(2)} %</dd>
                      </>
                      : null
                  }
                </dl>
                <hr />
                <div className="mb-2">
                  <dl>
                    {
                      this.props.boon.base_damage_mod !== null ?
                        <>
                          <dt>Base Damage Mod</dt>
                          <dd className="text-success">+{(this.props.boon.base_damage_mod * 100).toFixed(2)} %</dd>
                        </>
                      : null
                    }
                    {
                      this.props.boon.base_ac_mod !== null ?
                        <>
                          <dt>Base AC Mod</dt>
                          <dd className="text-success">+{(this.props.boon.base_ac_mod * 100).toFixed(2)} %</dd>
                        </>
                        : null
                    }
                    {
                      this.props.boon.base_healing_mod !== null ?
                        <>
                          <dt>Base Healing Mod</dt>
                          <dd className="text-success">+{(this.props.boon.base_healing_mod * 100).toFixed(2)} %</dd>
                        </>
                        : null
                    }
                  </dl>
                </div>
              </div>
              : null
          }

          {
            this.state.canceling ?
              <div className="progress" style={{position: 'relative', height: '4px'}}>
                <div className="progress-bar progress-bar-striped indeterminate">
                </div>
              </div>
            :
              null
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
