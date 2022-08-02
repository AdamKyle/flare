import React, {Fragment} from "react";
import ActionsManager from "../../lib/game/actions/actions-manager";
import ComponentLoading from "../../components/ui/loading/component-loading";
import MonsterActions from "./components/small-actions/monster-actions";
import ActionsTimers from "./components/actions-timers";
import ActionsProps from "../../lib/game/types/actions/actions-props";
import ActionsState from "../../lib/game/types/actions/actions-state";
import DropDown from "../../components/ui/drop-down/drop-down";
import {CraftingOptions} from "../../lib/game/types/actions/crafting-type-options";
import CraftingSection from "./components/crafting-section";
import SuccessOutlineButton from "../../components/ui/buttons/success-outline-button";
import ExplorationSection from "./components/exploration-section";
import DuelPlayer from "./components/duel-player";
import SkyOutlineButton from "../../components/ui/buttons/sky-outline-button";
import JoinPvp from "./components/join-pvp";
import CelestialFight from "./components/celestial-fight";

export default class Actions extends React.Component<ActionsProps, ActionsState> {

    private actionsManager: ActionsManager;

    private attackTimeOut: any;

    private craftingTimeOut: any;

    private monsterUpdate: any;

    private pvpUpdate: any;

    private duelOptions: any;

    constructor(props: ActionsProps) {
        super(props);

        this.state = {
            monsters: [],
            characters_for_dueling: [],
            attack_time_out: 0,
            crafting_time_out: 0,
            crafting_type: null,
            duel_fight_info: null,
            loading: true,
            show_exploration: false,
            show_celestial_fight: false,
            show_duel_fight: false,
            show_join_pvp: false,
        }

        this.actionsManager = new ActionsManager(this);

        // @ts-ignore
        this.monsterUpdate = Echo.private('update-monsters-list-' + this.props.character.user_id);

        // @ts-ignore
        this.attackTimeOut = Echo.private('show-timeout-bar-' + this.props.character.user_id);

        // @ts-ignore
        this.craftingTimeOut = Echo.private('show-crafting-timeout-bar-' + this.props.character.user_id);

        // @ts-ignore
        this.pvpUpdate = Echo.private('update-pvp-attack-' + this.props.character.user_id);

        // @ts-ignore
        this.duelOptions = Echo.join('update-duel');
    }

    componentDidMount() {
        this.actionsManager.initialFetch();

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
        this.monsterUpdate.listen('Game.Maps.Events.UpdateMonsterList', (event: any) => {
            this.setState({
                monsters: event.monsters,
            });
        });

        // @ts-ignore
        this.duelOptions.listen('Game.Maps.Events.UpdateDuelAtPosition', (event: any) => {
            this.actionsManager.setCharactersForDueling(event.characters);
        });

        // @ts-ignore
        this.pvpUpdate.listen('Game.Battle.Events.UpdateCharacterPvpAttack', (event: any) => {
            this.setState({
                show_duel_fight: true,
                duel_fight_info: event.data,
            });
        });
    }

    openCrafting(type: CraftingOptions) {
        this.actionsManager.setCraftingType(type);
    }

    manageExploration() {
        this.setState({
            show_exploration: !this.state.show_exploration,
        })
    }

    manageDuel() {
        this.setState({
            show_duel_fight: !this.state.show_duel_fight
        });
    }

    manageJoinPvp() {
        this.setState({
            show_join_pvp: !this.state.show_join_pvp
        });
    }

    manageFightCelestial() {
        this.setState({
            show_celestial_fight: !this.state.show_celestial_fight,
        })
    }

    isLoading(): boolean {
        return this.state.loading || this.state.monsters.length === 0;
    }

    updateAttackTimer(timeLeft: number) {
        this.setState({
            attack_time_out: timeLeft
        });
    }

    updateCraftingTimer(timeLeft: number) {
        this.setState({
            crafting_time_out: timeLeft
        });
    }

    removeCraftingType() {
        this.actionsManager.removeCraftingSection();
    }

    resetDuelData() {
        this.setState({
            duel_fight_info: null,
        });
    }

    render() {
        if (this.isLoading()) {
            return <ComponentLoading />
        }

        console.log(this.state.characters_for_dueling, this.state.show_exploration);

        return (
            <div>
                <div className='grid md:grid-cols-4'>
                    <div className='md:col-start-1 md:col-span-1'>
                        {
                            !this.state.show_exploration && !this.state.show_duel_fight && !this.state.show_join_pvp && !this.state.show_celestial_fight && this.props.character !== null ?
                                <DropDown menu_items={this.actionsManager.buildCraftingList(this.openCrafting.bind(this))}
                                          button_title={'Craft/Enchant'}
                                          disabled={this.actionsManager.cannotCraft()}
                                          selected_name={this.actionsManager.getSelectedCraftingOption()}
                                />
                            : null
                        }

                        {
                            !this.state.show_duel_fight && !this.state.show_join_pvp && !this.state.show_celestial_fight ?
                                <div className='mb-4'>
                                    <SuccessOutlineButton button_label={'Exploration'}
                                                          on_click={this.manageExploration.bind(this)}
                                                          additional_css={'w-1/2'}
                                                          disabled={this.props.character.is_dead}
                                    />
                                </div>
                            : null
                        }

                        {
                            this.props.celestial_id !== 0 && !this.state.show_exploration && !this.state.show_duel_fight && !this.state.show_join_pvp ?
                                <div className='mb-4'>
                                    <SuccessOutlineButton button_label={'Fight Celestial!'} on_click={this.manageFightCelestial.bind(this)} additional_css={'w-1/2'} disabled={this.props.character.is_dead || this.props.character.is_automation_running} />
                                </div>
                            : null
                        }

                        {
                            this.state.characters_for_dueling.length > 0 && !this.state.show_exploration && !this.state.show_join_pvp && !this.state.show_celestial_fight ?
                                <div className='mb-4'>
                                    <SuccessOutlineButton button_label={'Duel!'} on_click={this.manageDuel.bind(this)} additional_css={'w-1/2'} disabled={this.props.character.is_dead || this.props.character.is_automation_running} />
                                </div>
                            : null
                        }

                        {
                            this.props.character.can_register_for_pvp && !this.state.show_duel_fight && !this.state.show_exploration && !this.state.show_celestial_fight ?
                                <div className='mb-4'>
                                    <SkyOutlineButton button_label={'Join PVP'} on_click={this.manageJoinPvp.bind(this)} additional_css={'w-1/2'} disabled={this.props.character.is_dead} />
                                </div>
                            : null
                        }
                    </div>
                    <div className='md:col-start-2 md:col-span-3 mt-1'>
                        {
                            !this.state.show_exploration && !this.state.show_duel_fight && !this.state.show_join_pvp && !this.state.show_celestial_fight ?
                                <MonsterActions monsters={this.state.monsters}
                                                character={this.props.character}
                                                character_statuses={this.props.character_status}
                                                is_small={false}
                                >
                                    {
                                        this.state.crafting_type !== null ?
                                            <CraftingSection
                                                remove_crafting={this.removeCraftingType.bind(this)}
                                                type={this.state.crafting_type}
                                                character_id={this.props.character.id}
                                                cannot_craft={this.actionsManager.cannotCraft()}
                                            />
                                        : null
                                    }
                                </MonsterActions>
                            : null
                        }

                        {
                            this.state.show_duel_fight ?
                                <DuelPlayer characters={this.state.characters_for_dueling}
                                            duel_data={this.state.duel_fight_info}
                                            character={this.props.character}
                                            manage_pvp={this.manageDuel.bind(this)}
                                            reset_duel_data={this.resetDuelData.bind(this)}
                                />
                            : null
                        }

                        {
                            this.state.show_exploration ?
                                <ExplorationSection character={this.props.character}
                                                    manage_exploration={this.manageExploration.bind(this)}
                                                    monsters={this.state.monsters}
                                />
                            : null
                        }

                        {
                            this.state.show_celestial_fight ?
                                <CelestialFight character={this.props.character}
                                                manage_celestial_fight={this.manageFightCelestial.bind(this)}
                                                celestial_id={this.props.celestial_id}
                                                update_celestial={this.props.update_celestial}
                                />
                            : null
                        }

                        {
                            this.state.show_join_pvp ?
                                <JoinPvp manage_section={this.manageJoinPvp.bind(this)} character_id={this.props.character.id}/>
                                : null
                        }
                    </div>
                </div>
                <ActionsTimers attack_time_out={this.state.attack_time_out}
                               crafting_time_out={this.state.crafting_time_out}
                               update_attack_timer={this.updateAttackTimer.bind(this)}
                               update_crafting_timer={this.updateCraftingTimer.bind(this)}
                />
            </div>
        )
    }
}
