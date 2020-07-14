import React, { Children, isValidElement, cloneElement } from 'react';
import BattleAction from '../battle/battle-action';
import AdditionalCoreActionsDropDown from './additional-core-actions-dropdown';

export default class CoreActionsSection extends React.Component {

	constructor(props) {
		super(props);

		this.state = {
			isDead: false,
			isAdventuring: false,
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
			}, () => {
				this.props.setCharacterId(this.state.character.id);
			});
		});
	}

	characterIsDead(isDead) {
		this.setState({
			isDead: isDead,
		});
	}

	characterIsAdventuring(adventuring) {
		this.setState({
			isAdventuring: adventuring,
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
							<AdditionalCoreActionsDropDown
								isDead={this.state.isDead}
								isAdventuring={this.state.isAdventuring}
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
							isCharacterAdventuring={this.characterIsAdventuring.bind(this)}
							changeCraftingType={this.changeCraftingType.bind(this)}
							updateCanCraft={this.updateCanCraft.bind(this)}
						/>
					</div>
				</div>
			</div>
		);
	}
}