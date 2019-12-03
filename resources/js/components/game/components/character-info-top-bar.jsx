import React from 'react';

export default class CharacterInfoTopBar extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      characterSheet: null,
      isLoading: true,
    }

    this.topBar  = Echo.private('update-top-bar-' + this.props.userId);
  }

  componentDidMount() {
    axios.get('/api/character-sheet/' + this.props.characterId)
      .then((result) => {
        this.setState({
          characterSheet: result.data.sheet.data,
          isLoading: false,
        });
      });

    this.topBar.listen('Game.Battle.Events.UpdateTopBarBroadcastEvent', (event) => {
      console.log(event);
      this.setState({
        characterSheet: event.characterSheet.data,
      });
    });
  }

  render() {
    if (this.state.isLoading) { return 'Please wait ...'; }

    const sheet = this.state.characterSheet;

    const xpValue = sheet.xp / sheet.xp_next * 100;

    return (
      <div className="card character-top-bar mb-4">
        <div className="card-body">
          <div className="row">
            <div className="col-md-4">
              <span className="title">Name:</span> <span className="value">{sheet.name}</span>
            </div>
            <div className="col-md-4">
              <span className="title">Race:</span> <span className="value">{sheet.race}</span>
            </div>
            <div className="col-md-4">
              <span className="title">Class:</span> <span className="value">{sheet.class}</span>
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
                    style={{width: xpValue + '%'}}
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
              <span className="title">Strength:</span> <span className="value">{sheet.str}</span>
            </div>
            <div className="col-md-2">
              <span className="title">Durabillity:</span> <span className="value">{sheet.dur}</span>
            </div>
            <div className="col-md-2">
              <span className="title">Dexterity:</span> <span className="value">{sheet.dex}</span>
            </div>
            <div className="col-md-2">
              <span className="title">Intelligence:</span> <span className="value">{sheet.int}</span>
            </div>
            <div className="col-md-2">
              <span className="title">Charisma:</span> <span className="value">{sheet.chr}</span>
            </div>
          </div>
        </div>
      </div>
    )
  }
}
