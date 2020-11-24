import React, { Children, isValidElement, cloneElement } from 'react';
import BattleAction from '../battle/battle-action';
import AdditionalCoreActionsDropDown from './additional-core-actions-dropdown';
import CardTemplate from './templates/card-template';
import ContentLoader, { Facebook } from 'react-content-loader';

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
        character: result.data.character,
        monsters: result.data.monsters,
        isLoading: false,
        isDead: result.data.character.is_dead,
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

  updateShowEnchanting(show) {
    this.setState({
      showEnchanting: show,
    });
  }

  updateCanCraft(can) {
    this.setState({
      canCraft: can,
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

    return (
      <CardTemplate>
        <div className="row justify-content-center">
          <div className="col-md-2">
            <AdditionalCoreActionsDropDown
              isDead={this.state.isDead}
              isAdventuring={this.state.isAdventuring}
              changeCraftingType={this.changeCraftingType.bind(this)}
              updateShowCrafting={this.updateShowCrafting.bind(this)}
              updateShowEnchanting={this.updateShowEnchanting.bind(this)}
              canCraft={this.state.canCraft}
            />
          </div>
          <BattleAction
            userId={this.props.userId}
            character={this.state.character}
            monsters={this.state.monsters}
            showCrafting={this.state.showCrafting}
            showEnchanting={this.state.showEnchanting}
            isDead={this.state.isDead}
            shouldChangeCraftingType={this.state.changeCraftingType}
            isCharacterDead={this.characterIsDead.bind(this)}
            isCharacterAdventuring={this.characterIsAdventuring.bind(this)}
            changeCraftingType={this.changeCraftingType.bind(this)}
            updateCanCraft={this.updateCanCraft.bind(this)}
            canAttack={this.props.canAttack}
          />
        </div>
      </CardTemplate>
    );
  }
}