import React from "react";
import RaidFightProps from "./types/raid-fight-props";
import ServerFight from "./fight-section/server-fight";
import BattleMesages from "./fight-section/battle-mesages";
import RaidFightState from "./types/raid-fight-state";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";

export default class RaidFight extends React.Component<RaidFightProps, RaidFightState> {

    constructor(props: any) {
        super(props);

        this.state = {
            is_attacking: false,
            battle_messages: [],
            character_current_health: 0,
            monster_current_health: 0,
        }
    }

    componentDidMount(): void {
        this.setState({
            character_current_health: this.props.character_current_health,
            monster_current_health: this.props.monster_current_health,
        })
    }

    componentDidUpdate(): void {
        if (this.state.character_current_health !== this.props.character_current_health && this.props.revived) {
            this.setState({
                character_current_health: this.props.character_current_health,
                battle_messages: [],
            }, () => {
                this.props.reset_revived();
            });
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
            (new Ajax()).setRoute('raid-fight/'+this.props.character_id+'/' + this.props.monster_id).setParameters({
                attack_type: type,
            }).doAjaxCall('post', (result: AxiosResponse) => {
                console.log(result.data);
                this.setState({
                    character_current_health: result.data.character_current_health,
                    monster_current_health: result.data.monster_current_health,
                    battle_messages: result.data.messages,
                    is_attacking: false,
                });
            }, (error: AxiosError) => {
                console.error(error);

                this.setState({
                    is_attacking: false,
                })
            });
        });
    }

    render() {
        return (
            <ServerFight 
                monster_health={this.state.monster_current_health}
                character_health={this.state.character_current_health}
                monster_max_health={this.props.monster_max_health}
                character_max_health={this.props.character_max_health}
                monster_name={this.props.monster_name}
                preforming_action={this.state.is_attacking}
                character_name={this.props.character_name}
                is_dead={this.props.is_dead}
                can_attack={this.props.can_attack}
                monster_id={this.props.monster_id}
                attack={this.attack.bind(this)}
                revive={this.props.revive}
            >
                <BattleMesages is_small={this.props.is_small} battle_messages={this.state.battle_messages} />
            </ServerFight>
        );
    }

}