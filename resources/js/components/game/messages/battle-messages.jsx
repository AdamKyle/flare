import React from 'react';

export default class BattleMessages extends React.Component {
	constructor(props) {
		super(props);

		this.state = {
			characterCurrentHealth: 0,
			characterMaxHealth: 0,
			monsterMaxHealth: 0,
			monsterCurrentHealth: 0,
			isDead: false,
			attackFunc: null,
			battleMessages: [],
		}
	}

	componentDidUpdate(prevProps) {
		if (prevProps !== this.state) {
			this.setState(...prevProps);
		}
	}

	battleMessages() {
    return this.state.battleMessages.map((message) => {
      return <div key={message.message}><span className="battle-message">{message.message}</span> <br /></div>
    });
	}
	
	healthMeters() {
    if (this.state.monsterCurrentHealth <= 0) {
      return null;
    }

    let characterCurrentHealth = 0;

    if (this.state.characterCurrentHealth !== 0 && this.state.characterMaxHealth !== 0) {
      characterCurrentHealth = (this.state.characterCurrentHealth / this.state.characterMaxHealth) * 100;
    }

    const monsterCurrentHealth   = (this.state.monsterCurrentHealth / this.state.monsterMaxHealth) * 100;

    return (
      <div className="health-meters mb-2 mt-2">
        <div className="progress character mb-2">
          <div className="progress-bar character-bar" role="progressbar"
            style={{width: characterCurrentHealth + '%'}}
            aria-valuenow={this.state.characterCurrentHealth} aria-valuemin="0"
            aria-valuemax={this.state.characterMaxHealth}>{this.state.character.name}</div>
        </div>
        <div className="progress monster mb-2">
          <div className="progress-bar monster-bar" role="progressbar"
            style={{width: monsterCurrentHealth + '%'}}
            aria-valuenow={this.state.monsterCurrentHealth} aria-valuemin="0"
            aria-valuemax={this.state.monsterMaxHealth}>{this.state.monster.name}</div>
        </div>
      </div>
    );
	}


	render() {
		return (
			<>
				<hr />
        <div className="battle-section text-center">
          {this.state.monsterCurrentHealth !== 0 && !this.state.character.is_dead
            ?
            <>
              <button className="btn btn-primary" onClick={this.attack.bind(this)}>Attack</button>
              {this.healthMeters()}
            </>
            : null
          }
          {this.state.character.is_dead
            ? 
            <>
            <button className="btn btn-primary" onClick={this.revive.bind(this)}>Revive</button>
            <p className="mt-3">You are dead. Click revive to live again.</p>
            </>
            : null
          }
          {this.battleMessages()}
        </div>
			</>
		)
	}
}