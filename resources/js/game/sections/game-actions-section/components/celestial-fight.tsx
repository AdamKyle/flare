import clsx from "clsx";
import React from "react";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import {AxiosError, AxiosResponse} from "axios";
import Ajax from "../../../lib/ajax/ajax";
import {BattleMessage} from "./types/battle-message-type";
import ServerFight from './fight-section/server-fight';
import BattleMesages from "./fight-section/battle-mesages";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";

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
            error_message: null,
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

            this.setState({loading: false});

            if (typeof error.response !== 'undefined') {
                const response = error.response;

                this.setState({
                    error_message: response.data.message,
                })
            }
        });

        this.celestialFight.listen('Game.Battle.Events.UpdateCelestialFight', (event: any) => {
            this.props.update_celestial(0)

            this.setState({
                monster_health: event.data.celestial_fight_over ? 0 : event.data.monster_current_health,
                battle_messages: [event.data.who_killed],
            })
        })
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
                        character_health: result.data.health.current_character_health,
                        monster_health: result.data.health.current_monster_health,
                    })
                }, (error: AxiosError) => {
                    this.setState({loading: false});

                    if (typeof error.response !== 'undefined') {
                        const response = error.response;

                        this.setState({
                            error_message: response.data.message,
                        })
                    }
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
                            console.error(error);;
                        });
        });
    }

    render() {
        return (
            this.state.loading ?
                <ComponentLoading />
            :
                this.state.error_message === null ?
                    <ServerFight
                        monster_health={this.state.monster_health}
                        character_health={this.state.character_health}
                        monster_max_health={this.state.monster_max_health}
                        character_max_health={this.state.character_max_health}
                        monster_name={this.state.monster_name}
                        preforming_action={this.state.preforming_action}
                        character_name={this.props.character.name}
                        is_dead={this.props.character.is_dead}
                        can_attack={this.props.character.can_attack}
                        monster_id={this.props.celestial_id}
                        attack={this.attack.bind(this)}
                        manage_server_fight={this.props.manage_celestial_fight}
                        revive={this.revive.bind(this)}
                    >
                        <BattleMesages is_small={this.props.is_small} battle_messages={this.state.battle_messages} />
                    </ServerFight>
                :
                    <DangerAlert additional_css={'my-4'}>
                        {this.state.error_message}
                    </DangerAlert>
        )
    }
}
