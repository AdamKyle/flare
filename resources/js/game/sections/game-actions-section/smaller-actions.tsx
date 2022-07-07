import React, { Fragment } from "react";
import ActionsProps from "../../lib/game/types/actions/actions-props";
import ActionsState from "../../lib/game/types/actions/actions-state";
import {capitalize} from "lodash";
import Select from "react-select";
import CraftingSection from "./components/crafting-section";
import ActionsManager from "../../lib/game/actions/actions-manager";
import MonsterSelection from "./components/monster-selection";
import FightSection from "./components/fight-section";
import DropDown from "../../components/ui/drop-down/drop-down";
import clsx from "clsx";
import TimerProgressBar from "../../components/ui/progress-bars/timer-progress-bar";
import PrimaryButton from "../../components/ui/buttons/primary-button";
import MapMovementActions from "./components/small-actions/map-movement-actions";
import ExplorationSection from "./components/exploration-section";
import WarningAlert from "../../components/ui/alerts/simple-alerts/warning-alert";
import CelestialFight from "./components/celestial-fight";
import DuelPlayer from "./components/duel-player";
import {CraftingOptions} from "../../lib/game/types/actions/crafting-type-options";
import JoinPvp from "./components/join-pvp";

export default class SmallerActions extends React.Component<ActionsProps, ActionsState> {

    private attackTimeOut: any;

    private craftingTimeOut: any;

    private mapTimeOut: any;

    private monsterUpdate: any;

    private pvpUpdate: any;

    private duelOptions: any;

    private actionsManager: ActionsManager;

    constructor(props: ActionsProps) {
        super(props);

        this.state = {
            selected_action: null,
            loading: true,
            is_same_monster: false,
            character: null,
            monsters: [],
            monster_to_fight: null,
            attack_time_out: 0,
            crafting_time_out: 0,
            character_revived: false,
            can_player_move: true,
            crafting_type: null,
            movement_time_out: 0,
            automation_time_out: 0,
            characters_for_dueling: [],
            duel_characters: [],
            show_duel_fight: false,
            show_join_pvp: false,
            duel_fight_info: null,
        }

        // @ts-ignore
        this.attackTimeOut = Echo.private('show-timeout-bar-' + this.props.character.user_id);

        // @ts-ignore
        this.craftingTimeOut = Echo.private('show-crafting-timeout-bar-' + this.props.character.user_id);

        // @ts-ignore
        this.mapTimeOut     = Echo.private('show-timeout-move-' + this.props.character.user_id);

        // @ts-ignore
        this.monsterUpdate = Echo.private('update-monsters-list-' + this.props.character.user_id);

        // @ts-ignore
        this.pvpUpdate = Echo.private('update-pvp-attack-' + this.props.character.user_id);

        // @ts-ignore
        this.duelOptions = Echo.join('update-duel');

        this.actionsManager = new ActionsManager(this);
    }

    componentDidMount() {

        this.actionsManager.initialFetch(this.props);

        // @ts-ignore
        this.attackTimeOut.listen('Game.Core.Events.ShowTimeOutEvent', (event: any) => {
            this.setState({
                attack_time_out: event.forLength,
            });
        });

        // @ts-ignore
        this.craftingTimeOut.listen('Game.Core.Events.ShowCraftingTimeOutEvent', (event: any) => {
            this.setState({
                crafting_time_out: event.timeout,
            });
        });

        // @ts-ignore
        this.mapTimeOut.listen('Game.Maps.Events.ShowTimeOutEvent', (event: any) => {
            this.setState({
                movement_time_out: event.forLength,
                can_player_move: event.canMove,
            });
        });

        // @ts-ignore
        this.monsterUpdate.listen('App.Game.Maps.Events.UpdateMonsterList', (event: any) => {
            this.setState({
                monsters: event.monster,
                monster_to_fight: null,
            })
        });

        // @ts-ignore
        this.duelOptions.listen('Game.Maps.Events.UpdateDuelAtPosition', (event: any) => {
            this.setState({
                characters_for_dueling: event.characters,
            }, () => this.setDuelCharacters() );
        });

        // @ts-ignore
        this.pvpUpdate.listen('Game.Battle.Events.UpdateCharacterPvpAttack', (event: any) => {
            this.setState({
                show_duel_fight: true,
                duel_fight_info: event.data,
            });
        });

        if (!this.props.character.can_move) {
            this.setState({
                movement_time_out: this.props.character.can_move_again_at,
            });
        }

        if (this.props.character.is_automation_running) {
            this.setState({
                automation_time_out: this.props.character.automation_completed_at,
            });
        }
    }

    componentDidUpdate(prevProps: Readonly<any>, prevState: Readonly<ActionsState>, snapshot?: any) {
        this.actionsManager.actionComponentUpdated(this.state, this.props)
    }

    showAction(data: any) {
        this.setState({
            selected_action: data.value,
        }, () => {
            if (data.value === 'pvp-fight') {
                this.setState({
                    show_duel_fight: true
                });
            }
        });
    }

    setDuelCharacters() {
        if (typeof this.state.characters_for_dueling !== 'undefined') {
            const characters = this.state.characters_for_dueling.filter((character) => {
                return character.character_position_x === this.props.character_position?.x &&
                       character.character_position_y === this.props.character_position?.y &&
                       character.game_map_id === this.props.character_position?.game_map_id &&
                       character.name !== this.props.character.name
            });

            this.setState({
                duel_characters: characters,
            });
        }
    }

    buildOptions() {
        const options = [{
            label: 'Exploration',
            value: 'explore'
        },{
            label: 'Craft',
            value: 'craft'
        }, {
            label: 'Map Movement',
            value: 'map-movement'
        }];

        if (!this.props.character.is_automation_running) {
            options.unshift({
                label: 'Fight',
                value: 'fight'
            });
        }

       if (typeof this.state.duel_characters !== 'undefined' && this.state.duel_characters.length > 0) {
           options.push({
               label: 'Pvp Fight',
               value: 'pvp-fight'
           });
       }

        if (this.props.celestial_id !== 0 && this.props.celestial_id !== null) {
            options.push({
                label: 'Celestial Fight',
                value: 'celestial-fight'
            });
        }

        if (this.props.character.can_register_for_pvp) {
            options.push({
                label: 'Join Monthly PVP',
                value: 'join-monthly-pvp'
            });
        }

        return options;
    }

    defaultSelectedAction() {
        if (this.state.selected_action !== null) {
            return [{
                label: capitalize(this.state.selected_action),
                value: this.state.selected_action,
            }];
        }

        return [{
            label: 'Please Select Action',
            value: '',
        }];
    }

    setCraftingType(type: CraftingOptions) {
        this.setState({
            crafting_type: type,
        });
    }

    removeCraftingType() {
        this.actionsManager.removeCraftingSection();
    }

    closeMonsterSection() {
        this.setState({
            selected_action: null,
        });
    }

    closeCraftingSection() {
        this.setState({
            selected_action: null,
        });
    }

    closeMapSection() {
        this.setState({
            selected_action: null,
        })
    }

    closeExplorationSection() {
        this.setState({
            selected_action: null,
        })
    }

    manageFightCelestial() {
        this.setState({
            selected_action: null,
        })
    }

    setSelectedMonster(monster: any) {
        this.actionsManager.setSelectedMonster(monster);
    }

    resetSameMonster() {
        this.actionsManager.resetSameMonster();
    }

    revive() {
        this.actionsManager.revive(this.props.character_id);
    }

    setAttackTimeOut(attack_time_out: number) {
        this.actionsManager.setAttackTimeOut(attack_time_out);
    }

    updateTimer() {
        this.actionsManager.updateTimer();
    }

    updateMapTimer(movement_time_out: number) {
        console.log(movement_time_out);
        this.setState({
            movement_time_out: movement_time_out
        });
    }

    updateCraftingTimer() {
        this.actionsManager.updateCraftingTimer();
    }

    resetRevived() {
        this.actionsManager.resetRevived();
    }

    manageDuel() {
        this.setState({
            selected_action: null,
            show_duel_fight: !this.state.show_duel_fight,
        });
    }

    manageJoinPvp() {
        this.setState({
            selected_action: null,
            show_join_pvp: !this.state.show_join_pvp,
        });
    }

    resetDuelData() {
        this.setState({
            duel_fight_info: null,
        });
    }

    createMonster() {
        return (
            <Fragment>
                <button type='button' onClick={this.closeMonsterSection.bind(this)} className='text-red-600 dark:text-red-500 absolute right-[5px] top-[5px]'>
                    <i className="fas fa-times-circle"></i>
                </button>
                <MonsterSelection monsters={this.state.monsters}
                                  update_monster={this.setSelectedMonster.bind(this)}
                                  timer_running={this.state.attack_time_out > 0}
                                  character={this.state.character}
                />

                {
                    this.state.character?.is_dead ?
                        <div className='text-center my-4'>
                            <PrimaryButton button_label={'Revive'} on_click={this.revive.bind(this)} additional_css={'mb-4'} disabled={!this.props.character_statuses?.can_attack}/>
                            <p>
                                You are dead. Please Revive.
                            </p>
                        </div>
                        : null
                }

                {
                    this.state.monster_to_fight !== null ?
                        <FightSection
                            set_attack_time_out={this.setAttackTimeOut.bind(this)}
                            monster_to_fight={this.state.monster_to_fight}
                            character={this.state.character}
                            is_same_monster={this.state.is_same_monster}
                            reset_same_monster={this.resetSameMonster.bind(this)}
                            character_revived={this.state.character_revived}
                            reset_revived={this.resetRevived.bind(this)}
                            is_small={true}
                        />
                        : null
                }
            </Fragment>
        );
    }

    showCrafting() {
        return (
            <Fragment>
                <button type='button' onClick={this.closeCraftingSection.bind(this)} className='text-red-600 dark:text-red-500 absolute right-[5px] top-[5px]'>
                    <i className="fas fa-times-circle"></i>
                </button>

                {
                    this.state.crafting_type !== null ?
                        <CraftingSection remove_crafting={this.removeCraftingType.bind(this)}
                                         type={this.state.crafting_type}
                                         character_id={this.props.character.id}
                                         cannot_craft={this.actionsManager.cannotCraft()}/>
                    :
                        <Fragment>
                            <DropDown menu_items={this.actionsManager.buildCraftingList(this.setCraftingType.bind(this))}
                                      button_title={'Craft/Enchant'} disabled={this.state.character?.is_dead || this.actionsManager.cannotCraft()}
                                      selected_name={this.actionsManager.getSelectedCraftingOption()}/>
                        </Fragment>
                }
            </Fragment>

        );
    }

    renderExploration() {
        return (
            <Fragment>
                <button type='button' onClick={this.closeExplorationSection.bind(this)} className='text-red-600 dark:text-red-500 absolute right-[5px] top-[5px]'>
                    <i className="fas fa-times-circle"></i>
                </button>

                <ExplorationSection character={this.state.character} manage_exploration={this.closeExplorationSection.bind(this)} monsters={this.state.monsters} />
            </Fragment>

        );
    }

    showMapMovement() {
        return (
            <Fragment>
                <button type='button' onClick={this.closeMapSection.bind(this)} className='text-red-600 dark:text-red-500 absolute right-[5px] top-[5px]'>
                    <i className="fas fa-times-circle"></i>
                </button>
                <MapMovementActions character={this.props.character}
                                    update_map_timer={this.updateMapTimer.bind(this)}
                                    currencies={this.props.currencies}
                                    is_automation_running={this.props.character.is_automation_running}
                />
            </Fragment>
        );
    }

    showCelestialFight() {
        return (
            <CelestialFight character={this.props.character}
                            manage_celestial_fight={this.manageFightCelestial.bind(this)}
                            celestial_id={this.props.celestial_id}
                            update_celestial={this.props.update_celestial}
            />
        )
    }

    showDuelFight() {
        return (
            <DuelPlayer characters={this.state.duel_characters}
                        duel_data={this.state.duel_fight_info}
                        character={this.props.character}
                        manage_pvp={this.manageDuel.bind(this)}
                        reset_duel_data={this.resetDuelData.bind(this)}
            />
        )
    }

    showJoinPVP() {
        return (
            <JoinPvp manage_section={this.manageJoinPvp.bind(this)} character_id={this.props.character.id}/>
        )
    }

    buildSection() {
        switch(this.state.selected_action) {
            case 'fight':
                return this.createMonster();
            case 'explore':
                return this.renderExploration();
            case 'craft':
                return this.showCrafting();
            case 'map-movement':
                return this.showMapMovement();
            case 'celestial-fight':
                return this.showCelestialFight();
            case 'pvp-fight':
                return this.showDuelFight();
            case 'join-monthly-pvp':
                return this.showJoinPVP();
            default:
                return null;
        }
    }

    render() {
        return(
          <Fragment>
              {
                  this.state.selected_action !== null ?
                      <Fragment>
                          {
                              this.state.show_duel_fight ?
                                  this.showDuelFight()
                              :
                                  this.buildSection()
                          }
                      </Fragment>
                  :
                      <Fragment>
                          {
                              this.props.character.is_automation_running ?
                                  <div className='my-2'>
                                      <WarningAlert>
                                          Automation is running, You cannot fight monsters. <a href='/information/automation' target='_blank'>See Automation Help <i
                                          className="fas fa-external-link-alt"></i></a> for more details.
                                      </WarningAlert>
                                  </div>
                              : null
                          }
                          {
                              this.state.show_duel_fight ?
                                  this.showDuelFight()
                              :
                                  <Select
                                      onChange={this.showAction.bind(this)}
                                      options={this.buildOptions()}
                                      menuPosition={'absolute'}
                                      menuPlacement={'bottom'}
                                      styles={{menuPortal: (base: any) => ({...base, zIndex: 9999, color: '#000000'})}}
                                      menuPortalTarget={document.body}
                                      value={this.defaultSelectedAction()}
                                  />
                          }
                      </Fragment>
              }

              <div className='relative top-[18px] bottom-[10px]'>
                  <div className={clsx('grid gap-2', {
                      'md:grid-cols-2': this.state.attack_time_out !== 0 && this.state.crafting_time_out !== 0
                  })}>
                      <div>
                          <TimerProgressBar time_remaining={this.state.attack_time_out} time_out_label={'Attack Timeout'} update_time_remaining={this.updateTimer.bind(this)} />
                      </div>
                      <div>
                          <TimerProgressBar time_remaining={this.state.crafting_time_out} time_out_label={'Crafting Timeout'} update_time_remaining={this.updateCraftingTimer.bind(this)} />
                      </div>
                  </div>
              </div>

              <div className='relative top-[18px]'>
                  <div className={clsx('grid gap-2', {
                      'md:grid-cols-2': this.state.movement_time_out !== 0 && this.state.automation_time_out !== 0
                  })}>
                      {
                          typeof this.state.movement_time_out !== 'undefined'?
                              <div>
                                  <TimerProgressBar time_remaining={this.state.movement_time_out} time_out_label={'Movement Timeout'} />
                              </div>
                          : null
                      }

                      {
                          typeof this.state.automation_time_out !== 'undefined'?
                              <div>
                                  <TimerProgressBar time_remaining={this.state.automation_time_out} time_out_label={'Exploration'} />
                              </div>
                              : null
                      }
                  </div>
              </div>
          </Fragment>
        );
    }
}
