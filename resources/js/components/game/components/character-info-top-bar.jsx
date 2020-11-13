import React from 'react';
import CardTemplate from './templates/card-template';
import ForcedNameChange from './modals/forced-name-change';
import ContentLoader, { Facebook } from 'react-content-loader';

export default class CharacterInfoTopBar extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      characterSheet: null,
      isLoading: true,
      forceNameChange: false,
    }

    this.topBar          = Echo.private('update-top-bar-' + this.props.userId);
    this.forceNameChange = Echo.private('force-name-change-' + this.props.userId);
  }

  componentDidMount() {
    axios.get('/api/character-sheet/' + this.props.characterId)
      .then((result) => {
        this.setState({
          characterSheet: result.data.sheet,
          isLoading: false,
          forceNameChange: result.data.sheet.force_name_change,
        });
      });

    this.topBar.listen('Game.Core.Events.UpdateTopBarBroadcastEvent', (event) => {
      this.setState({
        characterSheet: event.characterSheet,
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
        <CardTemplate>
          <ContentLoader viewBox="0 0 380 30">
            {/* Only SVG shapes */}    
            <rect x="0" y="0" rx="4" ry="4" width="250" height="5" />
            <rect x="0" y="8" rx="3" ry="3" width="250" height="5" />
            <rect x="0" y="16" rx="4" ry="4" width="250" height="5" />
          </ContentLoader>
        </CardTemplate>
      );
    }

    const sheet = this.state.characterSheet;

    const xpValue = sheet.xp / sheet.xp_next * 100;

    return (
      <CardTemplate otherClasses="character-top-bar mb-4" loadingStatus={this.state.isLoading}>
        <div className="row">
          <div className="col-md-3">
            <span className="title">Name:</span> <span className="value">{sheet.name}</span>
          </div>
          <div className="col-md-3">
            <span className="title">Race:</span> <span className="value">{sheet.race}</span>
          </div>
          <div className="col-md-3">
            <span className="title">Class:</span> <span className="value">{sheet.class}</span>
          </div>
          <div className="col-md-3">
            <span className="title">Gold:</span> <span className="value">{sheet.gold}</span>
          </div>
        </div>
        <hr />
        <div className="row">
          <div className="col-md-2">
            <span className="title">Level:</span> <span className="value">{sheet.level}</span>
          </div>
          <div className="col-md-2">
            <span className="title">AC:</span> <span className="value">{sheet.ac}</span>
          </div>
          <div className="col-md-2">
            <span className="title">Health:</span> <span className="value">{sheet.health}</span>
          </div>
          <div className="col-md-2">
            <span className="title">Attack:</span> <span className="value">{sheet.attack}</span>
          </div>
          <div className="col-md-4 xp-bar">
            <span className="title">Xp:</span>
            <span className="value">
              <div className="progress level-bar mb-2">
                <div className="progress-bar skill-bar" role="progressbar"
                  style={{ width: xpValue + '%' }}
                  aria-valuenow={sheet.xp} aria-valuemin="0"
                  aria-valuemax={sheet.xp_next}
                >
                  {sheet.xp}
                </div>
              </div>
            </span>
          </div>
        </div>
        <div className="row">
          <div className="col-md-2">
            <span className="title">Strength:</span> <span className="value">{Math.round(sheet.str_modded)}</span>
          </div>
          <div className="col-md-2">
            <span className="title">Durability:</span> <span className="value">{Math.round(sheet.dur_modded)}</span>
          </div>
          <div className="col-md-2">
            <span className="title">Dexterity:</span> <span className="value">{Math.round(sheet.dex_modded)}</span>
          </div>
          <div className="col-md-2">
            <span className="title">Intelligence:</span> <span className="value">{Math.round(sheet.int_modded)}</span>
          </div>
          <div className="col-md-2">
            <span className="title">Charisma:</span> <span className="value">{Math.round(sheet.chr_modded)}</span>
          </div>
        </div>

        { this.state.forceNameChange
          ? <ForcedNameChange characterId={this.props.characterId}/> : null
        }
      </CardTemplate>
    )
  }
}
