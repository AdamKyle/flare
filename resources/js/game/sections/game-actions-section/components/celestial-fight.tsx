import clsx from "clsx";
import AttackButton from "../../../components/ui/buttons/attack-button";
import HealthMeters from "./health-meters";
import React, {Fragment} from "react";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import {AxiosError, AxiosResponse} from "axios";
import Ajax from "../../../lib/ajax/ajax";
import DangerButton from "../../../components/ui/buttons/danger-button";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import {BattleMessage} from "../../../lib/game/actions/battle/types/battle-message-type";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";

export default class CelestialFight extends React.Component<any, any> {

    private celestialFight: any;

    constructor(props: any) {
        super(props);

        this.state = {
            monster_health: 0,
            character_health: 0,
            monster_max_health: 0,
            character_max_health: 0,
            monster_name: '',
            battle_messages: [],
            loading: true,
            preforming_action: false,
        }

        // @ts-ignore
        this.celestialFight = Echo.join('celestial-fight-changes');
    }

    componentDidMount() {
        (new Ajax()).setRoute('celestial-fight/'+this.props.character.id+'/' + this.props.celestial_id).doAjaxCall('get',
        (result: AxiosResponse) => {
            this.setState({
                monster_health: result.data.fight.monster.current_health,
                character_health: result.data.fight.character.current_health,
                monster_max_health: result.data.fight.monster.max_health,
                character_max_health: result.data.fight.character.max_health,
                monster_name: result.data.fight.monster_name,
                loading: false,
            })
        }, (error: AxiosError) => {
            console.log(error);
        });

        this.celestialFight.listen('Game.Battle.Events.UpdateCelestialFight', (event: any) => {
            this.props.update_celestial(0)

            this.setState({
                monster_health: event.data.celestial_fight_over ? 0 : event.data.monster_current_health,
                battle_messages: [event.data.who_killed],
            })
        })
    }

    attackButtonDisabled() {
        return this.state.monster_health <= 0 || this.state.character_health <= 0 || this.props.character.is_dead || !this.props.character.can_attack || this.props.celestial_id === 0
    }

    attack(type: string) {
        this.setState({
            preforming_action: true,
        }, () => {
            (new Ajax()).setRoute('attack-celestial/'+this.props.character.id+'/' + this.props.celestial_id).setParameters({
                attack_type: type,
            }).doAjaxCall('post',
                (result: AxiosResponse) => {

                    this.setState({
                        preforming_action: false,
                        battle_messages: result.data.logs,
                        character_health: result.data.health.character_health,
                        monster_health: result.data.health.monster_health,
                    })
                }, (error: AxiosError) => {
                    console.log(error);
                });
        });

    }

    revive() {
        this.setState({
            preforming_action: true,
        }, () => {
            (new Ajax()).setRoute('celestial-revive/'+this.props.character.id)
                        .doAjaxCall('post',(result: AxiosResponse) => {
                            this.setState({
                                monster_health: result.data.fight.monster.current_health,
                                character_health: result.data.fight.character.current_health,
                                monster_max_health: result.data.fight.monster.max_health,
                                character_max_health: result.data.fight.character.max_health,
                                preforming_action: false,
                            })
                        }, (error: AxiosError) => {
                            console.log(error);
                        });
        });
    }

    renderBattleMessages() {
        if (this.props.is_small && this.state.battle_messages.length > 0) {
            const message = this.state.battle_messages.filter((battleMessage: BattleMessage) => battleMessage.message.includes('resurrect') || battleMessage.message.includes('has been defeated!'))

            if (message.length > 0) {
                return <p className='text-red-500 dark:text-red-400'>{message[0].message}</p>
            } else {
                return <p className='text-blue-500 dark:text-blue-400'>Attack child!</p>
            }
        }

        return this.state.battle_messages.filter((value: BattleMessage, index: number, self: BattleMessage[]) => {
            return index === self.findIndex((t: BattleMessage) => {
                return t.message === value.message && t.type === value.type;
            });
        }).map((battleMessage: BattleMessage) => {
            return <p className={clsx(
                {
                    'text-green-700 dark:text-green-400': this.typeCheck(battleMessage.type, 'player-action')
                }, {
                    'text-red-500 dark:text-red-400': this.typeCheck(battleMessage.type, 'enemy-action')
                }, {
                    'text-blue-500 dark:text-blue-400': this.typeCheck(battleMessage.type, 'regular')
                }
            )}>
                {battleMessage.message}
            </p>
        });
    }

    typeCheck(battleType: 'regular' | 'player-action' | 'enemy-action', type: 'regular' | 'player-action' | 'enemy-action'): boolean {
        return battleType === type;
    }

    render() {
        return (
            this.state.loading ?
                    <ComponentLoading />
            :
                <Fragment>
                    <div className={clsx('mt-4 mb-4 text-xs text-center', {
                        'hidden': this.attackButtonDisabled()
                    })}>
                        <AttackButton additional_css={'btn-attack'} icon_class={'ra ra-sword'} on_click={() => this.attack('attack')} disabled={this.attackButtonDisabled() || this.state.preforming_action}/>
                        <AttackButton additional_css={'btn-cast'} icon_class={'ra ra-burning-book'} on_click={() => this.attack('cast')} disabled={this.attackButtonDisabled() || this.state.preforming_action}/>
                        <AttackButton additional_css={'btn-cast-attack'} icon_class={'ra ra-lightning-sword'} on_click={() => this.attack('cast_and_attack')} disabled={this.attackButtonDisabled() || this.state.preforming_action}/>
                        <AttackButton additional_css={'btn-attack-cast'} icon_class={'ra ra-lightning-sword'} on_click={() => this.attack('attack_and_cast')} disabled={this.attackButtonDisabled() || this.state.preforming_action}/>
                        <AttackButton additional_css={'btn-defend'} icon_class={'ra ra-round-shield'} on_click={() => this.attack('defend')} disabled={this.attackButtonDisabled() || this.state.preforming_action}/>
                        <a href='/information/combat' target='_blank' className='ml-2'>Help <i
                            className="fas fa-external-link-alt"></i></a>
                    </div>
                    {
                        this.state.monster_max_health > 0 ?
                            <div className={clsx('mb-4 max-w-md m-auto', {
                                'mt-4': this.attackButtonDisabled()
                            })}>
                                <HealthMeters is_enemy={true} name={this.state.monster_name} current_health={parseInt(this.state.monster_health)} max_health={this.state.monster_max_health} />
                                <HealthMeters is_enemy={false} name={this.props.character.name} current_health={parseInt(this.state.character_health)} max_health={this.state.character_max_health} />
                            </div>
                            : null
                    }
                    {
                        this.state.preforming_action ?
                            <div className='w-1/2 ml-auto mr-auto'>
                                <LoadingProgressBar />
                            </div>
                        : null
                    }
                    <div className='italic text-center mb-4'>
                        {this.renderBattleMessages()}
                    </div>
                    <div className='text-center'>
                        <DangerButton button_label={'Leave Fight'} on_click={this.props.manage_celestial_fight} additional_css={'mr-4'} disabled={this.props.character.is_dead}/>
                        {
                            this.props.character.is_dead ?
                                <PrimaryButton button_label={'Revive'} on_click={this.revive.bind(this)} disabled={!this.props.character.can_attack}/>
                            : null
                        }
                    </div>
                </Fragment>
        )
    }
}
