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
import SmithyWorkBench from "../smithy-work-bench/SmithyWorkBench";
import LockedLocationType from "./lib/LockedLocationType";

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
      showSmithingBench: false,
      characterId: null,
      isLoading: true,
      character: null,
      monsters: null,
      canCraft: true,
      monster: null,
      actionComponent: 'battle-action',
      resetBattleAction: false
    };

    this.updateMonstersList    = Echo.private('update-monsters-list-' + this.props.userId);
    this.updateActions         = Echo.private('update-character-base-stats-' + this.props.userId);
    this.updateCharacterStatus = Echo.private('update-character-status-' + this.props.userId);
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

    this.updateActions.listen('Game.Core.Events.UpdateBaseCharacterInformation', (event) => {
      let baseStats = JSON.parse(JSON.stringify(event.baseStats));
      let character = JSON.parse(JSON.stringify(this.state.character));

      Object.keys(baseStats).forEach(function(el){
        if (typeof baseStats[el] === 'string') {
          if (baseStats[el].indexOf(',') !== -1) {
            baseStats[el] = parseFloat(baseStats[el].replace(/,/g, ''))
          }
        } else {
          baseStats[el] = parseFloat(baseStats[el]) || baseStats[el]
        }
      });

      Object.keys(baseStats).filter(key => key in character).forEach(key => {
        character[key] = baseStats[key];
      });

      this.setState({
        character: character
      });
    });

    this.updateMonstersList.listen('Game.Maps.Events.UpdateMonsterList', (event) => {
      const oldFirstMonsterName = this.state.monsters[0].name;
      const newFirstMonsterName = event.monsters[0].name;

      const oldMonster = this.state.monsters[0];
      const newMonster = event.monsters[0];

      this.setState({
        monsters: event.monsters,
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

    this.updateCharacterStatus.listen('Game.Battle.Events.UpdateCharacterStatus', (event) => {
      this.setState({
        isDead: event.data.is_dead,
        cannotAutoAttack: event.data.automation_locked,
      })
    });
  }

  componentDidUpdate(prevProps, prevState, snapshot) {
    if (this.props.celestial !== prevProps.celestial && this.state.actionComponent !== 'battle-action') {
      this.setState({
        actionComponent: 'battle-action',
      });
    }

    if (this.props.lockedLocationType !== LockedLocationType.PURGATORYSMITHSHOUSE && this.state.showSmithingBench) {
      this.setState({
        showSmithingBench: false,
      });
    }
  }

  updateResetBattleAction() {
    this.setState({
      resetBattleAction: false,
    })
  }

  updateCharacterHealth(health) {
    let character = this.state.character;

    character.health = health;

    this.setState({
      character: character
    });
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

  updateShowSmithingBench(show) {
    this.setState({
      showSmithingBench: show,
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
          <i className="ra ra-muscle-fat"></i> Exploration
        </span>
      )
    }

    return (
      <span>Exploration</span>
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
                  lockedLocationType={this.props.lockedLocationType}
                  isDead={this.state.isDead}
                  isAdventuring={this.state.isAdventuring}
                  isAlchemyLocked={this.state.character.is_alchemy_locked}
                  changeCraftingType={this.changeCraftingType.bind(this)}
                  updateShowCrafting={this.updateShowCrafting.bind(this)}
                  updateShowEnchanting={this.updateShowEnchanting.bind(this)}
                  updateShowAlchemy={this.updateShowAlchemy.bind(this)}
                  updateShowSmithingBench={this.updateShowSmithingBench.bind(this)}
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
                  this.props.kingdomData.can_mass_embezzle && !_.isEmpty(this.props.kingdomData.my_kingdoms) ?
                    <div className="mb-1">
                      <button className="btn btn-success btn-sm mb-2"
                              disabled={this.state.isDead || this.state.isAdventuring || this.props.attackAutomationIsRunning}
                              onClick={() => this.props.openMassEmbezzleModal(true)}
                              >Mass Embezzle</button>
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
                {
                  this.props.kingdomData.is_mine ?
                    <div className="mb-1">
                      <button disabled={this.state.isDead || this.state.isAdventuring}
                              onClick={() => this.props.openAbandonKingdom()}
                              className="btn btn-danger btn-sm mb-2">Abandon Kingdom</button>
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
                  this.state.showSmithingBench ?
                    <SmithyWorkBench characterId={this.state.character.id} openTimeOutModal={this.props.openTimeOutModal} userId={this.props.userId} updateCanCraft={this.updateCanCraft.bind(this)}/>
                  : null
                }
                {
                  this.state.actionComponent === 'battle-action' ?
                    <FightSection
                      character={this.state.character}
                      monster={this.state.monster}
                      userId={this.props.userId}
                      isCharacterDead={this.characterIsDead.bind(this)}
                      isDead={this.state.isDead}
                      setMonster={this.setMonster.bind(this)}
                      canAttack={this.props.canAttack}
                      isAdventuring={this.state.isAdventuring}
                      openTimeOutModal={this.props.openTimeOutModal}
                      resetBattleAction={this.state.resetBattleAction}
                      updateResetBattleAction={this.updateResetBattleAction.bind(this)}
                      updateCharacterHealth={this.updateCharacterHealth.bind(this)}
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
          <Tab eventKey="exploration" title={this.buildAutomationAttackTabTitle()} disabled={this.state.cannotAutoAttack || this.state.isAdventuring || this.state.isDead}>
            <AutoAttackSection
              character={this.state.character}
              isDead={this.state.isDead}
              monsters={this.state.monsters}
              userId={this.props.userId}
              openTimeOutModal={this.props.openTimeOutModal}
              attackAutomationIsRunning={this.props.attackAutomationIsRunning}
              waitingOnAttackAutomationCheck={this.props.waitingOnAttackAutomationCheck}
            />
          </Tab>
          <Tab eventKey="help" title={"Help"}>
            <p class="mb-2 mt-4">
              The first tab is your "Actions tab" this is where you select a monster to kill, or begin crafting/enchanting from the
              crafting/enchanting drop down list.
            </p>
            <p>
              You can fight monsters manually - through selecting a monster, clicking again and clicking on the of the five attack buttons or you can do
              Exploration, which allows you to automate the fighting process, while you do other things like craft and enchant.
            </p>
            <p>
              Exploration can be set up on the exploration tab, while this is running you cannot manually fight monsters.
            </p>
            <p>
              You should probably head to the shop, anvil icon in the side bar, and spend some of that 1000 gold to buy better equipment before attempting to fight.
              Anything that is more expensive is better for you. If you are not a Ranger or a Blacksmith you do not need a bow or hammer.
            </p>
            <h4>Essential links</h4>
            <ul>
              <li>
                <a href="/information/equipment" target="_blank">Equipment help <i className="fas fa-external-link-alt"></i></a>
              </li>
              <li>
                <a href="/information/combat" target="_blank">Combat help <i class="fas fa-external-link-alt"></i></a>
              </li>
              <li>
                <a href="/information/crafting" target="_blank">Crafting help <i class="fas fa-external-link-alt"></i></a>
              </li>
              <li>
                <a href="/information/enchanting" target="_blank">Enchanting help <i class="fas fa-external-link-alt"></i></a>
              </li>
              <li>
                <a href="/information/exploration" target="_blank">Exploration help <i class="fas fa-external-link-alt"></i></a>
              </li>
            </ul>
          </Tab>
        </Tabs>
      </Card>
    );
  }
}
