import React from 'react';
import {Col, Tab, Tabs} from 'react-bootstrap';
import ContentLoader from 'react-content-loader';
import BattleAction from '../battle/battle-action';
import AdditionalActionsDropDown from './components/additional-actions-drop-down';
import Card from '../components/templates/card';
import CraftingAction from '../crafting/crafting-action';
import EnchantingAction from '../enchanting/enchanting-action';
import FightSection from './fight-section';
import CelestialFightSection from "./celestial-fight-section";
import AlchemyAction from "../alchemy/alchemy-action";
import AutoAttackSection from "./auto-attack-section";

export default class ActionsSection extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      isDead: false,
      isAdventuring: false,
      cannotAutoAttack: false,
      changeCraftingType: false,
      showCrafting: false,
      showEnchanting: false,
      showAlchemy: false,
      characterId: null,
      isLoading: true,
      character: null,
      monsters: null,
      canCraft: true,
      monster: null,
      actionComponent: 'battle-action',
      resetBattleAction: false
    };

    this.updateActions = Echo.private('update-actions-' + this.props.userId);
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
        cannotAutoAttack: result.data.character.is_attack_automation_locked,
      }, () => {
        this.props.setCharacterId(this.state.character.id);
      });
    }).catch((err) => {
      if (err.hasOwnProperty('response')) {
        const response = err.response;

        if (response.status === 401) {
          return location.reload();
        }

        if (response.status === 429) {
          return this.props.openTimeOutModal();
        }
      }
    });

    this.updateActions.listen('Game.Maps.Events.UpdateActionsBroadcast', (event) => {
      const oldFirstMonsterName = this.state.monsters[0].name;
      const newFirstMonsterName = event.monsters[0].name;

      const oldMonster = this.state.monsters[0];
      const newMonster = event.monsters[0];

      this.setState({
        character: event.character,
        monsters: event.monsters,
        isDead: event.character.is_dead,
      }, () => {

        // We are on a new plane.
        if (oldFirstMonsterName !== newFirstMonsterName && !this.state.resetBattleAction) {
          this.setState({
            resetBattleAction: true,
          })
        }

        // We are at a location that increases the enemy stats.
        if (oldMonster.str !== newMonster.str) {
          this.setState({
            resetBattleAction: true,
          });
        }
      });
    });
  }

  componentDidUpdate(prevProps, prevState, snapshot) {
    if (this.props.celestial !== prevProps.celestial && this.state.actionComponent !== 'battle-action') {
      this.setState({
        actionComponent: 'battle-action',
      });
    }
  }

  updateResetBattleAction() {
    this.setState({
      resetBattleAction: false,
    })
  }

  characterIsDead(isDead, callback) {
    this.setState({
      isDead: isDead,
    }, () => {
      if (typeof callback === 'function') {
        callback();
      }
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

  updateShowAlchemy(show) {
    this.setState({
      showAlchemy: show,
    });
  }

  updateCanCraft(can) {
    this.setState({
      canCraft: can,
    });
  }

  setMonster(monster) {
    this.setState({
      monster: monster,
    });
  }

  switchBattleAction(type) {
    this.setState({
      actionComponent: type
    }, () => {
      if (type === 'battle-action') {
        this.props.updateCelestial(null);
      }
    });
  }

  buildAutomationAttackTabTitle() {
    if (this.props.attackAutomationIsRunning) {
      return (
        <span className="tw-text-green-600">
          <i className="ra ra-muscle-fat"></i> Auto Attack
        </span>
      )
    }

    return (
      <span>Auto Attack</span>
    )
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

    return (
      <Card>
        <Tabs defaultActiveKey="actions" id="auto-config-tab-section">
          <Tab eventKey="actions" title="Actions">
            <div className="row mt-4">
              <Col xs={12} sm={12} md={12} lg={12} xl={2}>
                <AdditionalActionsDropDown
                  isDead={this.state.isDead}
                  isAdventuring={this.state.isAdventuring}
                  isAlchemyLocked={this.state.character.is_alchemy_locked}
                  changeCraftingType={this.changeCraftingType.bind(this)}
                  updateShowCrafting={this.updateShowCrafting.bind(this)}
                  updateShowEnchanting={this.updateShowEnchanting.bind(this)}
                  updateShowAlchemy={this.updateShowAlchemy.bind(this)}
                  canCraft={this.state.canCraft}
                />
                {
                  this.props.kingdomData.can_attack && !_.isEmpty(this.props.kingdomData.my_kingdoms) ?
                    <div className="mb-1">
                      <button className="btn btn-success btn-sm mb-2" disabled={this.state.isDead || this.state.isAdventuring || this.props.attackAutomationIsRunning}
                              onClick={this.props.openKingdomAttackModal}>Attack Kingdom</button>
                    </div>
                    : null
                }

                {
                  this.props.kingdomData.can_settle ?
                    <div className="mb-1">
                      <button disabled={this.state.isDead || this.state.isAdventuring || this.props.attackAutomationIsRunning} onClick={this.props.openKingdomModal}
                              className="btn btn-success btn-sm mb-2">Settle Kingdom</button>
                    </div>
                    : null
                }

                {
                  this.props.kingdomData.is_mine ?
                    <div className="mb-1">
                      <button disabled={this.state.isDead || this.state.isAdventuring}
                              onClick={() => this.props.openKingdomManagement(true)}
                              className="btn btn-success btn-sm mb-2">Manage Kingdom</button>
                    </div>
                    : null
                }
                {
                  this.props.celestial !== null ?
                    <div className="mb-1">
                      <button disabled={this.state.isDead || this.state.isAdventuring || this.props.attackAutomationIsRunning}
                              onClick={() => this.switchBattleAction('celestial-fight')}
                              className="btn btn-success btn-sm mb-2">Fight Celestial!</button>
                    </div>
                    : null
                }
              </Col>
              <Col xs={12} sm={12} md={12} lg={12} xl={10}>
                {
                  this.state.actionComponent === 'battle-action' ?
                    <BattleAction
                      userId={this.props.userId}
                      character={this.state.character} x
                      monsters={this.state.monsters}
                      showCrafting={this.state.showCrafting}
                      showEnchanting={this.state.showEnchanting}
                      isDead={this.state.isDead}
                      shouldChangeCraftingType={this.state.changeCraftingType}
                      isCharacterDead={this.characterIsDead.bind(this)}
                      isCharacterAdventuring={this.characterIsAdventuring.bind(this)}
                      changeCraftingType={this.changeCraftingType.bind(this)}
                      updateCanCraft={this.updateCanCraft.bind(this)}
                      setMonster={this.setMonster.bind(this)}
                      canAttack={this.props.canAttack}
                      isAdventuring={this.state.isAdventuring}
                      shouldReset={this.state.resetBattleAction}
                      updateResetBattleAction={this.updateResetBattleAction.bind(this)}
                      attackAutomationIsRunning={this.props.attackAutomationIsRunning}
                    />
                    : this.props.celestial !== null ?
                      <div className="text-center mb-2">
                        <strong>{this.props.celestial.monster.name}</strong>
                      </div>
                      : null
                }

                <CraftingAction
                  isDead={this.state.isDead}
                  characterId={this.state.character.id}
                  showCrafting={this.state.showCrafting}
                  shouldChangeCraftingType={this.state.changeCraftingType}
                  changeCraftingType={this.changeCraftingType.bind(this)}
                  userId={this.props.userId}
                  characterGold={this.state.character.gold}
                  timeRemaining={this.state.character.can_craft_again_at}
                  updateCanCraft={this.updateCanCraft.bind(this)}
                  isAdventuring={this.state.isAdventuring}
                  openTimeOutModal={this.props.openTimeOutModal}
                />
                {
                  this.state.showAlchemy ?
                    <AlchemyAction
                      isDead={this.state.isDead}
                      characterId={this.state.character.id}
                      showAlchemy={this.state.showAlchemy}
                      userId={this.props.userId}
                      characterGoldDust={this.state.character.gold_dust}
                      characterShards={this.state.character.shards}
                      timeRemaining={this.state.character.can_craft_again_at}
                      updateCanCraft={this.updateCanCraft.bind(this)}
                      isAdventuring={this.state.isAdventuring}
                      openTimeOutModal={this.props.openTimeOutModal}
                    />
                    : null
                }
                {this.state.showEnchanting ?
                  <EnchantingAction
                    isDead={this.state.isDead}
                    characterId={this.state.character.id}
                    shouldChangeCraftingType={this.state.changeCraftingType}
                    changeCraftingType={this.changeCraftingType.bind(this)}
                    userId={this.props.userId}
                    characterGold={this.state.character.gold}
                    timeRemaining={this.state.character.can_craft_again_at}
                    updateCanCraft={this.updateCanCraft.bind(this)}
                    isAdventuring={this.state.isAdventuring}
                    openTimeOutModal={this.props.openTimeOutModal}
                  />
                  : null
                }

                {
                  this.state.actionComponent === 'battle-action' ?
                    <FightSection
                      character={this.state.character}
                      monster={this.state.monster}
                      userId={this.props.userId}
                      isCharacterDead={this.characterIsDead.bind(this)}
                      setMonster={this.setMonster.bind(this)}
                      canAttack={this.props.canAttack}
                      isAdventuring={this.state.isAdventuring}
                      openTimeOutModal={this.props.openTimeOutModal}
                      resetBattleAction={this.state.resetBattleAction}
                    />
                    :
                    this.props.celestial !== null ?
                      <CelestialFightSection
                        userId={this.props.userId}
                        characterId={this.state.character.id}
                        celestialId={this.props.celestial.id}
                        isDead={this.state.isDead}
                        isAdventuring={this.state.isAdventuring}
                        openTimeOutModal={this.props.openTimeOutModal}
                        characterName={this.state.character.name}
                        monsterName={this.props.celestial.monster.name}
                        switchBattleAction={this.switchBattleAction.bind(this)}
                      />
                      : null
                }

              </Col>
            </div>
          </Tab>
          <Tab eventKey="auto-attack" title={this.buildAutomationAttackTabTitle()} disabled={this.state.cannotAutoAttack}>
            <AutoAttackSection
              character={this.state.character}
              monsters={this.state.monsters}
              userId={this.props.userId}
              openTimeOutModal={this.props.openTimeOutModal}
              attackAutomationIsRunning={this.props.attackAutomationIsRunning}
            />
          </Tab>
        </Tabs>
      </Card>
    );
  }
}
