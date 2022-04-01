import React, {Fragment} from "react";
import AttackButton from "../../../components/ui/buttons/attack-button";
import BattleSetUp from "../../../lib/game/actions/battle/battle-setup";
import {BattleMessage} from "../../../lib/game/actions/battle/types/battle-message-type";
import clsx from "clsx";
import HealthMeters from "./health-meters";
import FightSectionProps from "../../../lib/game/actions/types/fight-section-props";
import Attack from '../../../lib/game/actions/battle/attack/attack/attack';
import AmbushHandler
    from "../../../lib/game/actions/battle/attack/attack/attack-types/ambush-and-counter/AmbushHandler";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";

export default class FightSection extends React.Component<FightSectionProps, any> {

    private battle_messages: BattleMessage[];

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
        }

        this.battle_messages = [];
    }

    componentDidMount() {
        this.setUpBattle();
    }

    componentDidUpdate() {
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

            this.battle_messages = [];
        }
    }

    setUpBattle() {

        if (this.props.character == null) {
            return;
        }

        const battleSetUp = new BattleSetUp(this.props.character, this.props.monster_to_fight);

        battleSetUp.setUp();

        const monsterHealth = battleSetUp.getMonsterHealth();

        this.battle_messages = battleSetUp.getMessages();

        const ambush        = new AmbushHandler();

        const healthObject = ambush.handleAmbush(this.props.character, battleSetUp.getMonsterObject(), this.props.character.health, monsterHealth, battleSetUp.getVoidanceResult().is_character_voided);

        this.battle_messages = [...this.battle_messages, ...ambush.getMessages()];

        if (healthObject.monster_health <= 0 || healthObject.character_health <= 0) {
            this.setState({
                monster_current_health: healthObject.monster_health <= 0 ? 0 : healthObject.monster_health,
                monster_max_health: monsterHealth,
                character_current_health: healthObject.character_health <= 0 ? 0 : healthObject.character_health,
                character_max_health: this.props.character.health,
                monster_to_fight_id: this.props.monster_to_fight.id,
                battle_messages: this.battle_messages,
                monster_to_fight: battleSetUp.getMonster(),
            });
        } else {
            this.setState({
                monster_current_health: healthObject.monster_health,
                monster_max_health: monsterHealth,
                character_current_health: healthObject.character_health,
                character_max_health: this.props.character.health,
                monster_to_fight_id: this.props.monster_to_fight.id,
                is_character_voided: battleSetUp.getVoidanceResult().is_character_voided,
                is_monster_voided: battleSetUp.getVoidanceResult().is_monster_voided,
                battle_messages: this.battle_messages,
                monster_to_fight: battleSetUp.getMonster(),
            });
        }
    }

    typeCheck(battleType: 'regular' | 'player-action' | 'enemy-action', type: 'regular' | 'player-action' | 'enemy-action'): boolean {
        return battleType === type;
    }

    renderBattleMessages() {
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

    attack(attackType: string) {
        const attack = new Attack(this.state.character_current_health, this.state.monster_current_health, this.state.is_character_voided, this.state.is_monster_voided);

        if (this.state.is_character_voided) {
            attackType = 'voided_' + attackType;
        }

        attack.attack(this.props.character, this.state.monster_to_fight, true, 'player', attackType);

        const attackState = attack.getState();

        this.setState({
            battle_messages: [...this.battle_messages, ...attackState.battle_messages],
            monster_current_health: attackState.monsterCurrentHealth,
            character_current_health: attackState.characterCurrentHealth,
        });

        this.battle_messages = [];

        (new Ajax()).setRoute('battle-results/' + this.props.character?.id).setParameters({
            is_character_dead: attackState.characterCurrentHealth <= 0,
            is_defender_dead: attackState.monsterCurrentHealth <= 0,
            monster_id: this.state.monster_to_fight_id,
        }).doAjaxCall('post', (result: AxiosResponse) => {
            this.props.set_attack_time_out(result.data.time_out);
        }, (error: AxiosError) => {
            console.log(error);
        })
    }

    attackButtonDisabled() {
        return this.state.monster_current_health <= 0 || this.state.character_current_health <= 0 || this.props.character?.is_dead || !this.props.character?.can_attack
    }

    render() {
        return (
            <Fragment>
                <div className='mt-4 mb-4 text-xs text-center'>
                    <AttackButton additional_css={'btn-attack'} icon_class={'ra ra-sword'} on_click={() => this.attack('attack')} disabled={this.attackButtonDisabled()}/>
                    <AttackButton additional_css={'btn-cast'} icon_class={'ra ra-burning-book'} on_click={() => this.attack('cast')} disabled={this.attackButtonDisabled()}/>
                    <AttackButton additional_css={'btn-cast-attack'} icon_class={'ra ra-lightning-sword'} on_click={() => this.attack('cast_and_attack')} disabled={this.attackButtonDisabled()}/>
                    <AttackButton additional_css={'btn-attack-cast'} icon_class={'ra ra-lightning-sword'} on_click={() => this.attack('attack_and_cast')} disabled={this.attackButtonDisabled()}/>
                    <AttackButton additional_css={'btn-defend'} icon_class={'ra ra-round-shield'} on_click={() => this.attack('defend')} disabled={this.attackButtonDisabled()}/>
                </div>
                {
                    this.state.monster_max_health > 0 && this.props.character !== null ?
                        <div className='mb-4 max-w-md m-auto'>
                            <HealthMeters is_enemy={true} name={this.props.monster_to_fight.name} current_health={this.state.monster_current_health} max_health={this.state.monster_max_health} />
                            <HealthMeters is_enemy={false} name={this.props.character.name} current_health={this.state.character_current_health} max_health={this.state.character_max_health} />
                        </div>
                    : null
                }
                <div className='italic text-center'>
                    {this.renderBattleMessages()}
                </div>
            </Fragment>
        )
    }

}
