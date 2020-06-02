import React, { Children, isValidElement, cloneElement } from 'react';
import BattleAction from '../battle/battle-action';
import AdditionalActionsDropDown from './additional-actions-dropdown';

export default class ActionsSection extends React.Component {

	constructor(props) {
		super(props);

		this.state = {
			isDead: false,
			changeCraftingType: false,
			showCrafting: false,
			characterId: null,
			isLoading: true,
			character: null,
			monsters: null,
			canCraft: true,
		};
	}

	componentDidMount() {
    axios.get('/api/actions', {
      params: {
        user_id: this.props.userId
      }
    }).then((result) => {
      this.setState({
        character: result.data.character.data,
				monsters: result.data.monsters,
        isLoading: false,
        isDead: result.data.character.data.is_dead,
      });
		});
	}

	characterIsDead(isDead) {
		this.setState({
			isDead: isDead,
		});
	}

	changeCraftingType(change) {
		this.setState({
			changeCraftingType: change,
		});
	}

	updateShowCrafting(show) {
		this.setState({
			showCrafting: show,
		});
	}

	updateCanCraft(can) {
		this.setState({
			canCraft: can,
		});
	}

	render() {
		
		if (this.state.isLoading) {
			return 'Please wait ...';
		}

		return (
			<div className="card">
				<div className="card-body">
					<div className="row justify-content-center">
						<div className="col-md-2">
							<AdditionalActionsDropDown 
								isDead={this.state.isDead} 
								changeCraftingType={this.changeCraftingType.bind(this)}
								updateShowCrafting={this.updateShowCrafting.bind(this)}
								canCraft={this.state.canCraft}
							/>
						</div>
						<BattleAction
							userId={this.props.userId}
							character={this.state.character}
							monsters={this.state.monsters}
              showCrafting={this.state.showCrafting}
              isDead={this.state.isDead}
							shouldChangeCraftingType={this.state.changeCraftingType}
							isCharacterDead={this.characterIsDead.bind(this)}
							changeCraftingType={this.changeCraftingType.bind(this)}
							updateCanCraft={this.updateCanCraft.bind(this)}
						/>
					</div>
				</div>
			</div>
		);
	}
}