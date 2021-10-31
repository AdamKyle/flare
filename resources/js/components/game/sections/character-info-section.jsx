import React from 'react';
import {Row, Col} from 'react-bootstrap';
import ContentLoader from 'react-content-loader';
import Card from '../components/templates/card';
import ForcedNameChange from './modals/forced-name-change';


export default class CharacterInfoTopSection extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      characterSheet: null,
      isLoading: true,
      forceNameChange: false,
    }

    this.topBar = Echo.private('update-top-bar-' + this.props.userId);
    this.forceNameChange = Echo.private('force-name-change-' + this.props.userId);
  }

  componentDidMount() {
    axios.get('/api/character-sheet/' + this.props.characterId)
      .then((result) => {
        this.setState({
          characterSheet: result.data.sheet,
          isLoading: false,
          forceNameChange: result.data.sheet.force_name_change,
        }, () => {
          if (result.data.sheet.timeout_until !== null) {
            this.props.openTimeOutModal(result.data.sheet.timeout_until)
          }

          this.props.updateCharacterGold(result.data.sheet.gold);
        });
      }).catch((err) => {
        if (err.hasOwnProperty('response')) {
          const response = err.response;

          if (response.status === 401) {
            return location.reload()
          }

          if (response.status === 429) {
            return this.props.openTimeOutModal()
          }
        }
      });

    this.topBar.listen('Game.Core.Events.UpdateTopBarBroadcastEvent', (event) => {
      this.setState({
        characterSheet: event.characterSheet,
      }, () => {
        this.props.updateCharacterGold(event.characterSheet.gold);
      });
    });

    this.forceNameChange.listen('Admin.Events.ForceNameChangeEvent', (event) => {
      this.setState({
        forceNameChange: event.character.force_name_change,
      });
    });
  }

  render() {
    if (this.state.isLoading) {
      return (
        <Card>
          <ContentLoader viewBox="0 0 380 30">
            <rect x="0" y="0" rx="4" ry="4" width="250" height="5"/>
            <rect x="0" y="8" rx="3" ry="3" width="250" height="5"/>
            <rect x="0" y="16" rx="4" ry="4" width="250" height="5"/>
          </ContentLoader>
        </Card>
      );
    }

    const sheet = this.state.characterSheet;

    const xpValue = sheet.xp / sheet.xp_next * 100;

    return (
      <Card otherClasses="character-top-bar mb-4" loadingStatus={this.state.isLoading}>
        <Row>
          <Col md={12} lg={12} xl={3}>
            <dl>
              <dt><strong>Name</strong>:</dt>
              <dd>{sheet.name}</dd>
            </dl>
          </Col>
          <Col md={12} lg={12} xl={3}>
            <dl>
              <dt><strong>Race</strong>:</dt>
              <dd>{sheet.race}</dd>
            </dl>
          </Col>
          <Col md={12} lg={12} xl={3}>
            <dl>
              <dt><strong>Class</strong>:</dt>
              <dd>{sheet.class}</dd>
            </dl>
          </Col>
          <Col md={12} lg={12} xl={3}>
            <dl>
              <dt><strong>Gold</strong>:</dt>
              <dd>{sheet.gold.toLocaleString('en-US', {maximumFractionDigits: 0})}</dd>
            </dl>
          </Col>
        </Row>
        <hr />
        <Row>
          <Col md={12} lg={12} xl={6}>
            <dl>
              <dt><strong>Gold Dust:</strong></dt>
              <dd>{sheet.gold_dust}</dd>
            </dl>
          </Col>
          <Col md={12} lg={12} xl={6}>
            <dl>
              <dt><strong>Cys. Shards:</strong></dt>
              <dd>{sheet.shards}</dd>
            </dl>
          </Col>
        </Row>
        <hr/>
        <Row>
          <Col md={12} lg={12} xl={2}>
            <dl>
              <dt><strong>Level</strong>:</dt>
              <dd>{sheet.level} / {sheet.max_level}</dd>
            </dl>
          </Col>
          <Col md={12} lg={12} xl={2}>
            <dl>
              <dt><strong>AC</strong>:</dt>
              <dd>{sheet.ac}</dd>
            </dl>
          </Col>
          <Col md={12} lg={12} xl={2}>
            <dl>
              <dt><strong>Health</strong>:</dt>
              <dd>{sheet.health}</dd>
            </dl>
          </Col>
          <Col md={12} lg={12} xl={2}>
            <dl>
              <dt><strong>Attack</strong>:</dt>
              <dd>{sheet.attack}</dd>
            </dl>
          </Col>
          <Col md={12} lg={12} xl={4}>
            <dl>
              <dt><strong>XP</strong>:</dt>
              <dd>
                <div className="progress level-bar mb-2">
                  <div className="progress-bar skill-bar" role="progressbar"
                       style={{width: xpValue + '%'}}
                       aria-valuenow={sheet.xp} aria-valuemin="0"
                       aria-valuemax={sheet.xp_next}
                  >
                    {Math.round(sheet.xp)}
                  </div>
                </div>
              </dd>
            </dl>
          </Col>
        </Row>
        <Row>
          <Col md={12} lg={12} xl={2}>
            <dl>
              <dt><strong>Strength</strong>:</dt>
              <dd>{sheet.str_modded}</dd>
            </dl>
          </Col>
          <Col md={12} lg={12} xl={2}>
            <dl>
              <dt><strong>Durability</strong>:</dt>
              <dd>{sheet.dur_modded}</dd>
            </dl>
          </Col>
          <Col md={12} lg={12} xl={2}>
            <dl>
              <dt><strong>Dexterity</strong>:</dt>
              <dd>{sheet.dex_modded}</dd>
            </dl>
          </Col>
          <Col md={12} lg={12} xl={2}>
            <dl>
              <dt><strong>Intelligence</strong>:</dt>
              <dd>{sheet.int_modded}</dd>
            </dl>
          </Col>
          <Col md={12} lg={12} xl={4}>
            <dl>
              <dt><strong>Charisma</strong>:</dt>
              <dd>{sheet.chr_modded}</dd>
            </dl>
          </Col>
        </Row>
        <Row>
          <Col md={12} lg={12} xl={2}>
            <dl>
              <dt><strong>Agi</strong>:</dt>
              <dd>{sheet.agi_modded}</dd>
            </dl>
          </Col>
          <Col md={12} lg={12} xl={2}>
            <dl>
              <dt><strong>Focus</strong>:</dt>
              <dd>{sheet.focus_modded}</dd>
            </dl>
          </Col>
        </Row>

        {
          this.state.forceNameChange ?
            <ForcedNameChange characterId={this.props.characterId}/>
            : null
        }
      </Card>
    )
  }
}
