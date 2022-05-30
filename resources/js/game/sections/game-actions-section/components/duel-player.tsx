import React, {Fragment} from "react";
import Select from "react-select";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import {AxiosResponse, AxiosError} from "axios";
import Ajax from "../../../lib/ajax/ajax";
import clsx from "clsx";
import AttackButton from "../../../components/ui/buttons/attack-button";
import HealthMeters from "./health-meters";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import DangerButton from "../../../components/ui/buttons/danger-button";
import {BattleMessage} from "../../../lib/game/actions/battle/types/battle-message-type";

export default class DuelPlayer extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            character_id: 0,
            show_attack_section: false,
            preforming_action: false,
            attacker_max_health: 0,
            attacker_health: 0,
            defender_max_health: 0,
            defender_health: 0,
            battle_messages: [],
        }
    }

    componentDidUpdate() {
        if (this.props.duel_data !== null) {
            this.setState({
                character_id: this.props.duel_data.attacker_id,
                attacker_max_health: this.props.duel_data.health_object.attacker_max_health,
                attacker_health: this.props.duel_data.health_object.attacker_health,
                defender_max_health: this.props.duel_data.health_object.defender_max_health,
                defender_health: this.props.duel_data.health_object.defender_health,
                battle_messages: this.props.duel_data.messages,
            }, () => {
                this.props.reset_duel_data();
            });
        }
    }

    buildCharacters() {
        return this.props.characters.map((character: {id: number, name: string}) => {
            if (character.name !== this.props.character.name) {
                return {
                    label: character.name,
                    value: character.id,
                }
            }
        }).filter((selectOptions: {label: string, value: number} | undefined) => typeof selectOptions !== 'undefined');
    }

    setCharacterToFight(data: any) {
        this.setState({
            character_id: data.value !== '' ? data.value : 0,
        });
    }

    defaultCharacter() {
        const foundCharacter = this.props.characters.filter((character: {id: number; name: string}) => {
            return character.id === this.state.character_id;
        });

        if (foundCharacter.length > 0) {
            return  {
                label: foundCharacter[0].name,
                value: foundCharacter[0].id
            }
        }

        return {
            label: 'Please select target',
            value: '',
        }
    }

    defenderName() {
        const foundCharacter = this.props.characters.filter((character: {id: number; name: string}) => {
            return character.id === this.state.character_id;
        });

        if (foundCharacter.length === 0) {
            return  'Error...'
        }

        return foundCharacter[0].name;
    }

    fight() {
        this.setState({
            preforming_action: true,
        }, () => {
            (new Ajax()).setRoute('attack-player/get-health/' + this.props.character.id).setParameters({
                defender_id: this.state.character_id
            }).doAjaxCall('get', (result: AxiosResponse) => {
                this.setState({
                    attacker_max_health: result.data.attacker_max_health,
                    attacker_health: result.data.attacker_health,
                    defender_max_health: result.data.defender_max_health,
                    defender_health: result.data.defender_health,
                    preforming_action: false,
                });
            })
        })
    }

    attackHidden() {
        return this.state.attacker_max_health === 0 || this.state.defender_max_health === 0 || this.props.characters.length === 0;
    }

    attack(type: string) {
        this.setState({
            preforming_action: true
        }, () => {
            (new Ajax()).setRoute('attack-player/' + this.props.character.id).setParameters({
                defender_id: this.state.character_id,
                attack_type: type,
            }).doAjaxCall('post', (result: AxiosResponse) => {
                this.setState({
                    preforming_action: false,
                });
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

    revive() {
        this.setState({
            preforming_action: true,
        }, () => {
            (new Ajax()).setRoute('pvp/revive/' + this.props.character.id).doAjaxCall('post', (result: AxiosResponse) => {
                this.setState({
                    preforming_action: false,
                })
            }, (error: AxiosError) => {});
        });
    }

    render() {
        return (
            <div className='mt-2 md:ml-[120px]'>
                <div className='grid grid-cols-3 gap-2'>
                    <div className='cols-start-1 col-span-2'>
                        <Select
                            onChange={this.setCharacterToFight.bind(this)}
                            options={this.buildCharacters()}
                            menuPosition={'absolute'}
                            menuPlacement={'bottom'}
                            styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                            menuPortalTarget={document.body}
                            value={this.defaultCharacter()}
                        />
                    </div>
                    <div className='cols-start-3 cols-end-3'>
                        <PrimaryButton button_label={'Attack'} on_click={this.fight.bind(this)} disabled={this.props.character.is_automation_running || this.props.character.is_dead || this.state.character_id === 0}/>
                    </div>
                </div>
                <p className='my-4 text-sm text-gray-700 dark:text-gray-300 w-2/3'>
                    <strong>Please note</strong>: The character could move at any moment. If they do and theres no one here, the entire duel section will vanish.
                </p>
                {
                    this.props.characters.length === 0 ?
                        <p className='my-4 text-sm text-center text-red-700 dark:text-red-500 w-2/3'>
                            No one left to fight child. Best be on your way. Click: Leave Fight.
                        </p>
                    : null
                }
                <div className='md:ml-[-100px]'>
                    <div className={clsx('mt-4 mb-4 text-xs text-center', {
                        'hidden': this.attackHidden()
                    })}>
                        <AttackButton additional_css={'btn-attack'} icon_class={'ra ra-sword'} on_click={() => this.attack('attack')} disabled={this.props.character.is_dead}/>
                        <AttackButton additional_css={'btn-cast'} icon_class={'ra ra-burning-book'} on_click={() => this.attack('cast')} disabled={this.props.character.is_dead}/>
                        <AttackButton additional_css={'btn-cast-attack'} icon_class={'ra ra-lightning-sword'} on_click={() => this.attack('cast_and_attack')} disabled={this.props.character.is_dead}/>
                        <AttackButton additional_css={'btn-attack-cast'} icon_class={'ra ra-lightning-sword'} on_click={() => this.attack('attack_and_cast')} disabled={this.props.character.is_dead}/>
                        <AttackButton additional_css={'btn-defend'} icon_class={'ra ra-round-shield'} on_click={() => this.attack('defend')} disabled={this.props.character.is_dead}/>
                        <a href='/information/combat' target='_blank' className='ml-2'>Help <i
                            className="fas fa-external-link-alt"></i></a>
                    </div>
                    {
                        this.state.defender_max_health > 0 && this.props.characters.length > 0 ?
                            <div className={clsx('mb-4 max-w-md m-auto', {
                                'mt-4': this.attackHidden()
                            })}>
                                <HealthMeters is_enemy={true} name={this.defenderName()} current_health={parseInt(this.state.defender_health)} max_health={this.state.defender_max_health} />
                                <HealthMeters is_enemy={false} name={this.props.character.name} current_health={parseInt(this.state.attacker_health)} max_health={this.state.attacker_max_health} />
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
                        <DangerButton button_label={'Leave Fight'} on_click={this.props.manage_pvp} additional_css={'mr-4'} disabled={this.props.character.is_dead}/>
                        {
                            this.props.character.is_dead ?
                                <PrimaryButton button_label={'Revive'} on_click={this.revive.bind(this)} disabled={!this.props.character.can_attack}/>
                                : null
                        }
                    </div>
                </div>
            </div>
        );
    }
}
