import React from "react";
import Ajax from "../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import ComponentLoading from "../../components/ui/loading/component-loading";
import DropDown from "../../components/ui/drop-down/drop-down";
import DangerButton from "../../components/ui/buttons/danger-button";
import MonsterSelection from "./components/monster-selection";
import CraftingSection from "./components/crafting-section";
import FightSection from "./components/fight-section";
import ActionsState from "../../lib/game/types/actions/actions-state";
import TimerProgressBar from "../../components/ui/progress-bars/timer-progress-bar";
import PrimaryButton from "../../components/ui/buttons/primary-button";
import {capitalize, isEqual} from "lodash";
import clsx from "clsx";
import ActionsProps from "../../lib/game/types/actions/actions-props";

export default class Actions extends React.Component<ActionsProps, ActionsState> {

    private attackTimeOut: any;

    private craftingTimeOut: any;

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
        }

        // @ts-ignore
        this.attackTimeOut = Echo.private('show-timeout-bar-' + this.props.character.user_id);

        // @ts-ignore
        this.craftingTimeOut = Echo.private('show-crafting-timeout-bar-' + this.props.character.user_id);
    }

    componentDidMount() {

        (new Ajax()).setRoute('actions/' + this.props.character_id).doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                character: this.props.character,
                monsters: result.data.monsters,
                attack_time_out: this.props.character.can_attack_again_at !== null ? this.props.character.can_attack_again_at : 0,
                loading: false,
            })
        }, (error: AxiosError) => {

        });

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
    }

    componentDidUpdate(prevProps: Readonly<any>, prevState: Readonly<ActionsState>, snapshot?: any) {

        if (this.state.character?.is_dead && !this.props.character.is_dead) {
            this.setState({
                character_revived: true,
            })
        }

        if (!isEqual(this.props.character, this.state.character)) {
            this.setState({
                character: this.props.character
            });
        }
    }

    openCrafting(type: 'craft' | 'enchant' | 'alchemy' | 'workbench' | 'trinketry' | null) {
        this.setState({
            crafting_type: type,
        });
    }

    removeCraftingType() {
        this.setState({
            crafting_type: null,
        });
    }

    attackKingdom() {
    }

    setSelectedMonster(monster: any) {
        let isSameMonster = false;

        if (monster.id === this.state.monster_to_fight?.id) {
            isSameMonster = true;
        }

        this.setState({
            monster_to_fight: monster,
            is_same_monster: isSameMonster,
        });
    }

    resetSameMonster() {
        this.setState({
            is_same_monster: false,
        });
    }

    revive() {
        (new Ajax()).setRoute('battle-revive/' + this.props.character?.id).doAjaxCall('post', (result: AxiosResponse) => {

        }, (error: AxiosError) => {

        });
    }

    setAttackTimeOut(attack_time_out: number) {
        this.setState({
            attack_time_out: attack_time_out
        });
    }

    updateTimer() {
        this.setState({
            attack_time_out: 0,
        })
    }

    updateCraftingTimer() {
        this.setState({
            crafting_time_out: 0,
        })
    }

    resetRevived() {
        this.setState({
            character_revived: false
        });
    }

    getSelectedCraftingOption() {
        if (this.state.crafting_type !== null) {
            return capitalize(this.state.crafting_type);
        }

        return '';
    }

    cannotCraft() {
        return this.state.crafting_time_out > 0 || !this.props.character_statuses?.can_craft
    }

    buildCraftingList() {
        const options = [
            {
                name: 'Craft',
                icon_class: 'ra ra-hammer',
                on_click: () => this.openCrafting('craft'),
            },
            {
                name: 'Enchant',
                icon_class: 'ra ra-burning-embers',
                on_click: () => this.openCrafting('enchant'),
            },
            {
                name: 'Trinketry',
                icon_class: 'ra ra-anvil',
                on_click: () => this.openCrafting('trinketry'),
            }
        ];

        if (!this.props.character.is_alchemy_locked) {
            options.splice(2, 0, {
                name: 'Alchemy',
                icon_class: 'ra ra-potion',
                on_click: () => this.openCrafting('alchemy'),
            });
        }

        if (this.props.character.can_use_work_bench) {
            if (typeof options[2] !== 'undefined') {
                options.splice(3, 0, {
                    name: 'Workbench',
                    icon_class: 'ra ra-anvil',
                    on_click: () => this.openCrafting('workbench'),
                })
            } else {
                options.splice(2, 0, {
                    name: 'Workbench',
                    icon_class: 'ra ra-anvil',
                    on_click: () => this.openCrafting('workbench'),
                });
            }
        }

        return options;
    }

    render() {

        return (
            <div className='px-4'>
                {
                    this.state.loading ?
                        <ComponentLoading />
                    :
                        <div className='grid md:grid-cols-4'>
                            <div className='md:col-start-1 md:col-span-1'>
                                <DropDown menu_items={this.buildCraftingList()} button_title={'Craft/Enchant'} disabled={this.state.character?.is_dead || this.cannotCraft()} selected_name={this.getSelectedCraftingOption()}/>
                                <DangerButton button_label={'Attack Kingdom'} on_click={this.attackKingdom.bind(this)} disabled={this.state.character?.is_dead} />
                            </div>
                            <div className='border-b-2 block border-b-gray-300 dark:border-b-gray-600 my-3 md:hidden'></div>
                            <div className='md:col-start-2 md:col-span-3 mt-1'>
                                <MonsterSelection monsters={this.state.monsters} update_monster={this.setSelectedMonster.bind(this)} timer_running={this.state.attack_time_out > 0} character={this.state.character}/>

                                {
                                    this.state.crafting_type !== null ?
                                        <CraftingSection remove_crafting={this.removeCraftingType.bind(this)} type={this.state.crafting_type} character_id={this.props.character.id} cannot_craft={this.cannotCraft()}/>
                                    : null
                                }

                                <div className={'md:ml-[-100px]'}>

                                    {
                                        this.state.character?.is_dead ?
                                            <div className='text-center my-4'>
                                                <PrimaryButton button_label={'Revive'} on_click={this.revive.bind(this)} additional_css={'mb-4'} />
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
                                            />
                                        : null
                                    }
                                </div>

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
