import React, {Fragment} from "react";
import AttackButton from "../../../components/ui/buttons/attack-button";
import BattleSetUp from "../../../lib/game/actions/battle/battle-setup";
import {BattleMessage} from "../../../lib/game/actions/battle/types/battle-message-type";
import clsx from "clsx";
import HealthMeters from "./health-meters";
import FightSectionProps from "../../../lib/game/actions/types/fight-section-props";

export default class FightSection extends React.Component<FightSectionProps, any> {

    constructor(props: FightSectionProps) {
        super(props);

        this.state = {
            battle_messages: [],
            character_current_health: 0,
            character_max_health: 0,
            monster_current_health: 0,
            monster_max_health: 0,
            monster_to_fight_id: 0,
        }
    }

    componentDidMount() {
        this.setUpBattle();
    }

    componentDidUpdate() {
        if (this.props.monster_to_fight.id !== this.state.monster_to_fight_id) {
            this.setUpBattle();
        }
    }

    setUpBattle() {

        if (this.props.character == null) {
            return;
        }

        const battleSetUp = new BattleSetUp(this.props.character, this.props.monster_to_fight);

        battleSetUp.setUp();

        const monsterHealth = battleSetUp.getMonsterHealth();

        this.setState({
            battle_messages: battleSetUp.getMessages(),
            monster_current_health: monsterHealth,
            monster_max_health: monsterHealth,
            character_current_health: this.props.character.health,
            character_max_health: this.props.character.health,
            monster_to_fight_id: this.props.monster_to_fight.id,
        });
    }

    typeCheck(battleType: 'regular' | 'player-action' | 'enemy-action', type: 'regular' | 'player-action' | 'enemy-action'): boolean {
        return battleType === type;
    }

    renderBattleMessages() {
        return this.state.battle_messages.map((battleMessage: BattleMessage) => {
            return <p className={clsx(
                {
                    'text-green-500 dark:text-green-400': this.typeCheck(battleMessage.type, 'player-action')
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

    render() {
        return (
            <Fragment>
                <div className='my-4 text-xs text-center lg:text-left lg:pl-16'>
                    <AttackButton additional_css={'btn-attack'} icon_class={'ra ra-sword'}/>
                    <AttackButton additional_css={'btn-cast'} icon_class={'ra ra-burning-book'}/>
                    <AttackButton additional_css={'btn-cast-attack'} icon_class={'ra ra-lightning-sword'}/>
                    <AttackButton additional_css={'btn-attack-cast'} icon_class={'ra ra-lightning-sword'}/>
                    <AttackButton additional_css={'btn-defend'} icon_class={'ra ra-round-shield'}/>
                </div>
                {
                    this.state.monster_max_health > 0 && this.props.character !== null ?
                        <div className='mb-8 max-w-md'>
                            <HealthMeters is_enemy={true} name={this.props.monster_to_fight.name} current_health={this.state.monster_current_health} max_health={this.state.monster_max_health} />
                            <HealthMeters is_enemy={false} name={this.props.character.name} current_health={this.state.character_current_health} max_health={this.state.character_max_health} />
                        </div>
                    : null
                }
                <div className='font-italic text-center lg:pr-36'>
                    {this.renderBattleMessages()}
                </div>
            </Fragment>
        )
    }

}
