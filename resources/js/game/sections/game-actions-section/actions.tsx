import React from "react";
import Ajax from "../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import ComponentLoading from "../../components/ui/loading/component-loading";
import DropDown from "../../components/ui/drop-down/drop-down";
import DangerButton from "../../components/ui/buttons/danger-button";
import MonsterSelection from "./components/monster-selection";
import CraftingSection from "./components/crafting-section";
import FightSection from "./components/fight-section";
import ActionsState from "../../lib/game/actions/types/actions-state";
import TimerProgressBar from "../../components/ui/progress-bars/timer-progress-bar";
import PrimaryButton from "../../components/ui/buttons/primary-button";

export default class Actions extends React.Component<any, ActionsState> {

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            is_same_monster: false,
            character: null,
            monsters: [],
            monster_to_fight: null,
            attack_time_out: 0,
        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('actions/' + this.props.character_id).doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                character: this.props.character,
                monsters: result.data.monsters,
                loading: false,
            })
        }, (error: AxiosError) => {

        });
    }

    openCrafting() {
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

    render() {
        return (
            <div className='px-4'>
                {
                    this.state.loading ?
                        <ComponentLoading />
                    :
                        <div className='grid md:grid-cols-4'>
                            <div className='md:col-start-1 md:col-span-1'>
                                <DropDown menu_items={[
                                    {
                                        name: 'Craft',
                                        icon_class: 'ra ra-hammer',
                                        on_click: this.openCrafting.bind(this)
                                    },
                                    {
                                        name: 'Enchant',
                                        icon_class: 'ra ra-burning-embers',
                                        on_click: this.openCrafting.bind(this)
                                    },
                                    {
                                        name: 'Alchemy',
                                        icon_class: 'ra ra-potion',
                                        on_click: this.openCrafting.bind(this)
                                    },
                                    {
                                        name: 'Work Bench',
                                        icon_class: 'ra ra-anvil',
                                        on_click: this.openCrafting.bind(this)
                                    }
                                ]} button_title={'Craft/Enchant'} />
                                <DangerButton button_label={'Attack Kingdom'} on_click={this.attackKingdom.bind(this)} />
                            </div>
                            <div className='border-b-2 block border-b-gray-300 dark:border-b-gray-600 my-3 md:hidden'></div>
                            <div className='md:col-start-2 md:col-span-3 mt-1'>
                                <MonsterSelection monsters={this.state.monsters} update_monster={this.setSelectedMonster.bind(this)} />
                                <CraftingSection />

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
                                        />
                                    : null
                                }

                            </div>
                        </div>
                }

                <div className='relative top-[24px]'>
                    <TimerProgressBar time_remaining={this.state.attack_time_out} time_out_label={'Attack Timeout'} update_time_remaining={this.updateTimer.bind(this)} />
                </div>
            </div>
        )
    }
}
