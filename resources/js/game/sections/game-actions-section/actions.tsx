 import React from "react";
import clsx from "clsx";
import ComponentLoading from "../../components/ui/loading/component-loading";
import DropDown from "../../components/ui/drop-down/drop-down";
import ActionsState from "../../lib/game/types/actions/actions-state";
import TimerProgressBar from "../../components/ui/progress-bars/timer-progress-bar";
import ActionsProps from "../../lib/game/types/actions/actions-props";
import ActionsManager from "../../lib/game/actions/actions-manager";
import SuccessOutlineButton from "../../components/ui/buttons/success-outline-button";
import MainActionSection from "./components/main-action-section";
import ExplorationSection from "./components/exploration-section";

export default class Actions extends React.Component<ActionsProps, ActionsState> {

    private attackTimeOut: any;

    private craftingTimeOut: any;

    private actionsManager: ActionsManager;

    private monsterUpdate: any;

    constructor(props: ActionsProps) {
        super(props);

        this.state = {
            loading: true,
            is_same_monster: false,
            character: null,
            monsters: [],
            monster_to_fight: null,
            attack_time_out: 0,
            crafting_time_out: 0,
            character_revived: false,
            crafting_type: null,
            show_exploration: false,
        }

        // @ts-ignore
        this.attackTimeOut = Echo.private('show-timeout-bar-' + this.props.character.user_id);

        // @ts-ignore
        this.craftingTimeOut = Echo.private('show-crafting-timeout-bar-' + this.props.character.user_id);

        // @ts-ignore
        this.monsterUpdate = Echo.private('update-monsters-list-' + this.props.character.user_id);

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
        this.monsterUpdate.listen('Game.Maps.Events.UpdateMonsterList', (event: any) => {
           this.setState({
               monsters: event.monsters,
               monster_to_fight: null,
           })
        });
    }

    componentDidUpdate(prevProps: Readonly<any>, prevState: Readonly<ActionsState>, snapshot?: any) {
        this.actionsManager.actionComponentUpdated(this.state, this.props)
    }

    manageExploration() {
        this.setState({
            show_exploration: !this.state.show_exploration,
        })
    }

    openCrafting(type: 'craft' | 'enchant' | 'alchemy' | 'workbench' | 'trinketry' | null) {
        this.actionsManager.setCraftingType(type);
    }

    removeCraftingType() {
        this.actionsManager.removeCraftingSection();
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

    updateCraftingTimer() {
        this.actionsManager.updateCraftingTimer();
    }

    resetRevived() {
        this.actionsManager.resetRevived();
    }

    getSelectedCraftingOption() {
        this.actionsManager.getSelectedCraftingOption();
    }

    cannotCraft() {
        return this.actionsManager.cannotCraft();
    }

    render() {
        return (
            <div className='lg:px-4'>
                {
                    this.state.loading ?
                        <ComponentLoading />
                    :
                        <div className='grid md:grid-cols-4'>
                            <div className='md:col-start-1 md:col-span-1'>
                                {
                                    !this.state.show_exploration ?
                                        <DropDown menu_items={this.actionsManager.buildCraftingList(this.openCrafting.bind(this))} button_title={'Craft/Enchant'} disabled={this.state.character?.is_dead || this.cannotCraft()} selected_name={this.actionsManager.getSelectedCraftingOption()}/>
                                    : null
                                }
                                <SuccessOutlineButton button_label={'Exploration'} on_click={this.manageExploration.bind(this)} additional_css={'w-1/2'} />
                            </div>
                            <div className='border-b-2 block border-b-gray-300 dark:border-b-gray-600 my-3 md:hidden'></div>
                            <div className='md:col-start-2 md:col-span-3 mt-1'>
                                {
                                    this.state.show_exploration ?
                                        <ExplorationSection character={this.state.character} manage_exploration={this.manageExploration.bind(this)} monsters={this.state.monsters} />
                                    :
                                        <MainActionSection monsters={this.state.monsters}
                                                           attack_time_out={this.state.attack_time_out}
                                                           crafting_type={this.state.crafting_type}
                                                           character={this.state.character}
                                                           character_revived={this.state.character_revived}
                                                           is_same_monster={this.state.is_same_monster}
                                                           monster_to_fight={this.state.monster_to_fight}
                                                           set_selected_monster={this.setSelectedMonster.bind(this)}
                                                           remove_crafting_type={this.removeCraftingType.bind(this)}
                                                           cannot_craft={this.cannotCraft()}
                                                           revive={this.revive.bind(this)}
                                                           set_attack_timeOut={this.setAttackTimeOut.bind(this)}
                                                           reset_same_monster={this.resetSameMonster.bind(this)}
                                                           reset_revived={this.resetRevived.bind(this)}
                                        />
                                }


                            </div>
                        </div>
                }

                <div className='relative top-[24px]'>
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
            </div>
        )
    }
}
