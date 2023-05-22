import React from "react";
import RaidFightProps from "./types/raid-fight-props";
import ServerFight from "./fight-section/server-fight";
import BattleMesages from "./fight-section/battle-mesages";
import RaidFightState from "./types/raid-fight-state";

export default class RaidFight extends React.Component<RaidFightProps, RaidFightState> {

    constructor(props: any) {
        super(props);

        this.state = {
            is_attacking: false,
            battle_messages: [],
        }
    }

    attackButtonDisabled(): boolean {
        return this.props.monster_current_health <= 0 || 
               this.props.character_current_health <= 0 || 
               this.props.is_dead || 
               !this.props.can_attack || 
               this.props.monster_id === 0 || 
               this.state.is_attacking;
    }

    attack(type: string): void {
        this.setState({
            is_attacking: true,
        }, () => {
            
            console.log('Hook up attack!');
        })
    }

    revive() {
        this.setState({
            is_attacking: true,
        }, () => {
            console.log('hook up revive!');
        })
    }

    render() {
        return (
            <ServerFight 
                monster_health={this.props.monster_current_health}
                character_health={this.props.character_current_health}
                monster_max_health={this.props.monster_max_health}
                character_max_health={this.props.character_max_health}
                monster_name={this.props.monster_name}
                preforming_action={this.state.is_attacking}
                character_name={this.props.character_name}
                is_dead={this.props.is_dead}
                can_attack={this.props.can_attack}
                monster_id={this.props.monster_id}
                attack={this.attack.bind(this)}
                revive={this.revive.bind(this)}
            >
                <BattleMesages is_small={this.props.is_small} battle_messages={this.state.battle_messages} />
            </ServerFight>
        );
    }

}