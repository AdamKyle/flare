import React, { Fragment } from "react";
import RaidFightProps from "./types/raid-fight-props";
import ServerFight from "./fight-section/server-fight";
import BattleMesages from "./fight-section/battle-mesages";
import RaidFightState from "./types/raid-fight-state";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import PrimaryLinkButton from "../../../components/ui/buttons/primary-link-button";

export default class RaidFight extends React.Component<
    RaidFightProps,
    RaidFightState
> {
    private attacksLeftUpdate: any;

    constructor(props: any) {
        super(props);

        this.state = {
            is_attacking: false,
            battle_messages: [],
            character_current_health: 0,
            monster_current_health: 0,
            attacks_left: 5,
            error_message: "",
        };

        // @ts-ignore
        this.attacksLeftUpdate = Echo.private(
            "update-raid-attacks-left-" + this.props.user_id,
        );
    }

    componentDidMount(): void {
        this.setState({
            character_current_health: this.props.character_current_health,
            monster_current_health: this.props.monster_current_health,
            attacks_left: this.props.initial_attacks_left,
        });

        // @ts-ignore
        this.attacksLeftUpdate.listen(
            "Game.Battle.Events.UpdateRaidAttacksLeft",
            (event: { attacksLeft: number }) => {
                this.setState({
                    attacks_left: event.attacksLeft,
                });
            },
        );
    }

    componentDidUpdate(): void {
        if (
            this.state.character_current_health !==
                this.props.character_current_health &&
            this.props.revived
        ) {
            this.setState(
                {
                    character_current_health:
                        this.props.character_current_health,
                },
                () => {
                    this.props.reset_revived();
                },
            );
        }

        if (this.props.update_raid_fight) {
            this.setState(
                {
                    character_current_health:
                        this.props.character_current_health,
                    monster_current_health: this.props.monster_current_health,
                    attacks_left: this.props.initial_attacks_left,
                    battle_messages: [],
                },
                () => {
                    this.props.reset_update();
                },
            );
        }
    }

    attack(type: string): void {
        this.setState(
            {
                is_attacking: true,
            },
            () => {
                new Ajax()
                    .setRoute(
                        "raid-fight/" +
                            this.props.character_id +
                            "/" +
                            this.props.monster_id,
                    )
                    .setParameters({
                        attack_type: type,
                    })
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState({
                                character_current_health:
                                    result.data.character_current_health,
                                monster_current_health:
                                    result.data.monster_current_health,
                                battle_messages: result.data.messages,
                                is_attacking: false,
                            });
                        },
                        (error: AxiosError) => {
                            console.error(error);

                            let response: AxiosResponse | null = null;

                            if (typeof error.response !== "undefined") {
                                response = error.response;
                            }

                            this.setState({
                                is_attacking: false,
                                error_message:
                                    response !== null
                                        ? response.data.message
                                        : "Unknown error occured!",
                            });
                        },
                    );
            },
        );
    }

    canAttack(): boolean {
        if (
            this.props.is_raid_boss &&
            this.state.attacks_left <= 0 &&
            !this.props.is_dead
        ) {
            return false;
        }

        return this.props.can_attack;
    }

    render() {
        return (
            <Fragment>
                {this.props.is_raid_boss ? (
                    <div className="flex items-center justify-center">
                        <div className="mt-4 text-center font-bold">
                            Attacks Left: {this.state.attacks_left}/5{" "}
                            {this.state.attacks_left <= 0
                                ? "[You can attack again tomorrow]"
                                : ""}
                        </div>
                    </div>
                ) : null}

                {this.state.error_message !== "" ? (
                    <div className="flex items-center justify-center">
                        <div className="mt-4 text-center text-red-700 dark:text-red-500">
                            {this.state.error_message}
                        </div>
                    </div>
                ) : null}

                <div className="flex items-center justify-center">
                    <div className=" mt-4 mb-4 text-center">
                        <PrimaryLinkButton
                            button_label={"Elemental Atonement Info"}
                            on_click={
                                this.props.manage_elemental_atonement_modal
                            }
                        />
                    </div>
                </div>

                <ServerFight
                    monster_health={this.state.monster_current_health}
                    character_health={this.state.character_current_health}
                    monster_max_health={this.props.monster_max_health}
                    character_max_health={this.props.character_max_health}
                    monster_name={this.props.monster_name}
                    preforming_action={this.state.is_attacking}
                    character_name={this.props.character_name}
                    is_dead={this.props.is_dead}
                    can_attack={this.canAttack()}
                    monster_id={this.props.monster_id}
                    attack={this.attack.bind(this)}
                    revive={this.props.revive}
                >
                    <BattleMesages
                        is_small={this.props.is_small}
                        battle_messages={this.state.battle_messages}
                    />
                </ServerFight>
            </Fragment>
        );
    }
}
