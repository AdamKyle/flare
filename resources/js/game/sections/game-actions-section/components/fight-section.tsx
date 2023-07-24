import React from "react";
import AttackButton from "../../../components/ui/buttons/attack-button";
import clsx from "clsx";
import HealthMeters from "./health-meters";
import FightSectionProps from "./types/fight-section-props";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import FightSectionState from "./types/fight-section-state";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import BattleMesages from "./fight-section/battle-mesages";
import Messages from '../../chat/components/messages';

export default class FightSection extends React.Component<FightSectionProps, FightSectionState> {

    constructor(props: FightSectionProps) {
        super(props);

        this.state = {
            battle_messages: [],
            character_current_health: 0,
            character_max_health: 0,
            monster_current_health: 0,
            monster_max_health: 0,
            monster_to_fight_id: 0,
            is_character_voided: false,
            is_monster_voided: false,
            monster_to_fight: null,
            processing_rank_battle: false,
            setting_up_rank_fight: false,
        }
    }

    componentDidMount() {
        if (this.props.is_rank_fight) {
            this.props.setup_rank_fight(this);

            return;
        }

        this.setUpBattle();
    }

    componentDidUpdate() {

        if (this.props.is_rank_fight) {

            if (this.state.setting_up_rank_fight) {
                return;
            }

            if (this.props.is_same_monster) {
                this.setState({
                    battle_messages: [],
                    setting_up_rank_fight: true,
                }, () => {
                    this.props.setup_rank_fight(this);
                });
            }

            if (this.props.monster_to_fight.id !== this.state.monster_to_fight_id && this.state.monster_to_fight_id !== 0) {
                this.setState({
                    battle_messages: [],
                    setting_up_rank_fight: true,
                }, () => {
                    this.props.setup_rank_fight(this);
                });
            }

            return;
        }

        if (this.props.monster_to_fight.id !== this.state.monster_to_fight_id) {
            this.setState({
                battle_messages: [],
            });

            this.setUpBattle();
        }

        if (this.props.is_same_monster) {
            this.setState({
                battle_messages: [],
            });

            this.setUpBattle();

            this.props.reset_same_monster();
        }

        if (this.props.character_revived) {
            this.setState({
                character_current_health: this.props.character?.health,
                character_max_health: this.props.character?.health,
                battle_messages: [],
            }, () => {
                this.props.reset_revived();
            });
        }
    }

    setUpBattle() {

        if (this.props.character == null) {
            return;
        }

        (new Ajax).setRoute('setup-monster-fight/'+this.props.character.id+'/' + this.props.monster_to_fight.id)
                  .setParameters({attack_type: 'attack'})
                  .doAjaxCall('get', (result: AxiosResponse) => {
                    this.setState({
                        battle_messages: result.data.opening_messages,
                        character_current_health: result.data.health.current_character_health,
                        character_max_health: result.data.health.max_character_health,
                        monster_current_health: result.data.health.current_monster_health,
                        monster_max_health: result.data.health.max_monster_health,
                        monster_to_fight_id: result.data.monster.id,
                    })
                  }, (error: AxiosError) => {
                    console.log(error);
                  });
    }

    attack(attackType: string) {

        if (this.props.is_rank_fight) {
            return this.props.process_rank_fight(this, attackType);
        }

        (new Ajax).setRoute('monster-fight/'+this.props.character.id)
                  .setParameters({attack_type: attackType})
                  .doAjaxCall('post', (result: AxiosResponse) => {
                    console.log(result);
                    this.setState({
                        battle_messages: result.data.messages,
                        character_current_health: result.data.health.current_character_health < 0 ? 0 : result.data.health.current_character_health,
                        monster_current_health: result.data.health.current_monster_health < 0 ? 0 : result.data.health.current_monster_health,
                    })
                  }, (error: AxiosError) => {
                    console.log(error);
                  });
    }

    attackButtonDisabled() {

        if (this.props.is_rank_fight) {

            if (this.props.character?.is_dead || !this.props.character?.can_attack) {
                return true;
            }

            if (this.state.monster_current_health <= 0) {
                return true;
            }

            return false;
        }

        if (typeof this.state.character_current_health === 'undefined') {
            return true;
        }

        return this.state.monster_current_health <= 0 || this.state.character_current_health <= 0 || this.props.character?.is_dead || !this.props.character?.can_attack
    }

    clearBattleMessages() {
        this.setState({
            battle_messages: [],
            monster_max_health: 0,
        })
    }

    render() {
        return (
            <div className={clsx({'ml-[-100px]': !this.props.is_small})}>
                <div className={clsx('mt-4 mb-4 text-xs text-center', {
                    'hidden': this.attackButtonDisabled(),
                    'ml-[50px]': !this.props.is_small && !this.props.is_rank_fight
                })}>
                    <AttackButton is_small={this.props.is_small} type={'Atk'} additional_css={'btn-attack'} icon_class={'ra ra-sword'} on_click={() => this.attack('attack')} disabled={this.attackButtonDisabled()}/>
                    <AttackButton is_small={this.props.is_small} type={'Cast'} additional_css={'btn-cast'} icon_class={'ra ra-burning-book'} on_click={() => this.attack('cast')} disabled={this.attackButtonDisabled()}/>
                    <AttackButton is_small={this.props.is_small} type={'Cast & Atk'} additional_css={'btn-cast-attack'} icon_class={'ra ra-lightning-sword'} on_click={() => this.attack('cast_and_attack')} disabled={this.attackButtonDisabled()}/>
                    <AttackButton is_small={this.props.is_small} type={'Atk & Cast'} additional_css={'btn-attack-cast'} icon_class={'ra ra-lightning-sword'} on_click={() => this.attack('attack_and_cast')} disabled={this.attackButtonDisabled()}/>
                    <AttackButton is_small={this.props.is_small} type={'Defend'} additional_css={'btn-defend'} icon_class={'ra ra-round-shield'} on_click={() => this.attack('defend')} disabled={this.attackButtonDisabled()}/>

                    {
                        !this.props.is_rank_fight ?
                            <a href='/information/combat' target='_blank' className='ml-2'>Help <i
                                className="fas fa-external-link-alt"></i></a>
                        : null
                    }

                </div>
                <div className={clsx('mt-1 text-xs text-center ml-[-50px] lg:ml-0', { 'hidden': this.attackButtonDisabled() })}>
                    <span className={'w-10 mr-4 ml-4'}>Atk</span>
                    <span className={'w-10 ml-6'}>Cast</span>
                    <span className={'w-10 ml-4'}>Cast & Atk</span>
                    <span className={'w-10 ml-2'}>Atk & Cast</span>
                    <span className={'w-10 ml-2'}>Defend</span>
                </div>
                {
                    this.state.processing_rank_battle ?
                        <div className='w-1/2 mx-auto'>
                            <LoadingProgressBar />
                        </div>
                    : null
                }
                {
                    this.attackButtonDisabled() ?
                        <div className='text-center mt-4'>
                            <button onClick={this.clearBattleMessages.bind(this)}
                                    className='text-red-500 dark:text-red-400 underline hover:text-red-600 dark:hover:text-red-500'>
                                Clear
                            </button>
                        </div>
                    : null
                }
                {
                    this.state.monster_max_health > 0 && this.props.character !== null ?
                        <div className={clsx('mb-4 max-w-md m-auto', {
                            'mt-4': this.attackButtonDisabled()
                        })}>
                            <HealthMeters is_enemy={true} name={this.props.monster_to_fight.name} current_health={this.state.monster_current_health} max_health={this.state.monster_max_health} />
                            <HealthMeters is_enemy={false} name={this.props.character.name} current_health={this.state.character_current_health} max_health={this.state.character_max_health} />
                        </div>
                    : null
                }
                <div className='italic text-center'>
                    <BattleMesages battle_messages={this.state.battle_messages} is_small={this.props.is_small} />
                </div>
            </div>
        )
    }

}
