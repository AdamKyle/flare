import React from "react";
import Select from "react-select";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import { AxiosResponse, AxiosError } from "axios";
import Ajax from "../../../lib/ajax/ajax";
import clsx from "clsx";
import AttackButton from "../../../components/ui/buttons/attack-button";
import HealthMeters from "./health-meters";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import DangerButton from "../../../components/ui/buttons/danger-button";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import { BattleMessageType } from "./types/battle-message-type";
import DuelPlayerProps, { CharactersList } from "./types/duel-player-props";
import DuelPlayerState from "./types/duel-player-state";
import { DuelMessages } from "../../../lib/game/types/core/duel-player/definitions/duel-data";
import BattleMesages from "./fight-section/battle-mesages";

export default class DuelPlayer extends React.Component<
    DuelPlayerProps,
    DuelPlayerState
> {
    constructor(props: DuelPlayerProps) {
        super(props);

        this.state = {
            character_id: 0,
            defender_id: 0,
            show_attack_section: false,
            preforming_action: false,
            attacker_max_health: 0,
            attacker_health: 0,
            defender_max_health: 0,
            defender_health: 0,
            battle_messages: [],
            error_message: null,
            defender_atonement: "N/A",
            attacker_atonement: "N/A",
        };
    }

    componentDidMount() {
        if (this.props.duel_data !== null) {
            this.setState(
                {
                    character_id: this.props.duel_data.attacker_id,
                    attacker_max_health:
                        this.props.duel_data.health_object.attacker_max_health,
                    attacker_health:
                        this.props.duel_data.health_object.attacker_health,
                    defender_max_health:
                        this.props.duel_data.health_object.defender_max_health,
                    defender_health:
                        this.props.duel_data.health_object.defender_health,
                    defender_id: this.props.duel_data.defender_id,
                    battle_messages: this.props.duel_data.messages,
                    defender_atonement: this.props.duel_data.defender_atonement,
                    attacker_atonement: this.props.duel_data.attacker_atonement,
                },
                () => {
                    this.props.reset_duel_data();
                },
            );
        }
    }

    componentDidUpdate() {
        if (this.props.duel_data !== null) {
            this.setState(
                {
                    character_id: this.props.duel_data.attacker_id,
                    attacker_max_health:
                        this.props.duel_data.health_object.attacker_max_health,
                    attacker_health:
                        this.props.duel_data.health_object.attacker_health,
                    defender_max_health:
                        this.props.duel_data.health_object.defender_max_health,
                    defender_health:
                        this.props.duel_data.health_object.defender_health,
                    defender_id: this.props.duel_data.defender_id,
                    battle_messages: this.props.duel_data.messages,
                    defender_atonement: this.props.duel_data.defender_atonement,
                    attacker_atonement: this.props.duel_data.attacker_atonement,
                },
                () => {
                    this.props.reset_duel_data();
                },
            );
        }
    }

    buildCharacters() {
        const selectedCharacter = this.props.character;

        const filteredCharacters = this.props.characters.filter(
            (character: CharactersList) =>
                character.id !== selectedCharacter.id,
        );

        return filteredCharacters.map((character: CharactersList) => ({
            label: character.name,
            value: character.id,
        }));
    }

    setCharacterToFight(data: any) {
        this.setState({
            character_id: data.value !== "" ? data.value : 0,
        });
    }

    defaultCharacter() {
        const foundCharacter = this.props.characters.filter(
            (character: { id: number; name: string }) => {
                return character.id === this.state.character_id;
            },
        );

        if (foundCharacter.length > 0) {
            return {
                label: foundCharacter[0].name,
                value: foundCharacter[0].id,
            };
        }

        return {
            label: "Please select target",
            value: "",
        };
    }

    defenderName() {
        const foundCharacter = this.props.characters.filter(
            (character: { id: number; name: string }) => {
                return character.id === this.state.defender_id;
            },
        );

        if (foundCharacter.length === 0) {
            return "Error...";
        }

        return foundCharacter[0].name;
    }

    fight() {
        this.setState(
            {
                preforming_action: true,
                error_message: null,
            },
            () => {
                new Ajax()
                    .setRoute(
                        "attack-player/get-health/" + this.props.character.id,
                    )
                    .setParameters({
                        defender_id: this.state.character_id,
                    })
                    .doAjaxCall("get", (result: AxiosResponse) => {
                        this.setState({
                            attacker_max_health:
                                result.data.attacker_max_health,
                            attacker_health: result.data.attacker_health,
                            defender_max_health:
                                result.data.defender_max_health,
                            defender_health: result.data.defender_health,
                            character_id: result.data.attacker_id,
                            defender_id: result.data.defender_id,
                            preforming_action: false,
                            attacker_atonement: result.data.attacker_atonement,
                            defender_atonement: result.data.defender_atonement,
                        });
                    });
            },
        );
    }

    attackHidden() {
        return (
            this.state.attacker_max_health === 0 ||
            this.state.defender_max_health === 0 ||
            this.props.characters.length === 0
        );
    }

    attack(type: string) {
        this.setState(
            {
                preforming_action: true,
                error_message: null,
            },
            () => {
                new Ajax()
                    .setRoute("attack-player/" + this.props.character.id)
                    .setParameters({
                        defender_id: this.state.defender_id,
                        attack_type: type,
                    })
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState({
                                preforming_action: false,
                            });
                        },
                        (error: AxiosError) => {
                            if (typeof error.response !== "undefined") {
                                const data = error.response.data;

                                this.setState({
                                    error_message: data.message,
                                    preforming_action: false,
                                });
                            }
                        },
                    );
            },
        );
    }

    revive() {
        this.setState(
            {
                preforming_action: true,
            },
            () => {
                new Ajax()
                    .setRoute("pvp/revive/" + this.props.character.id)
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState({
                                preforming_action: false,
                            });
                        },
                        (error: AxiosError) => {},
                    );
            },
        );
    }

    render() {
        return (
            <div className="mt-2 md:ml-[120px]">
                {this.state.error_message !== null ? (
                    <DangerAlert>{this.state.error_message}</DangerAlert>
                ) : null}
                <div className="mt-2 grid grid-cols-3 gap-2">
                    <div className="cols-start-1 col-span-2">
                        <Select
                            onChange={this.setCharacterToFight.bind(this)}
                            options={this.buildCharacters()}
                            menuPosition={"absolute"}
                            menuPlacement={"bottom"}
                            styles={{
                                menuPortal: (base) => ({
                                    ...base,
                                    zIndex: 9999,
                                    color: "#000000",
                                }),
                            }}
                            menuPortalTarget={document.body}
                            value={this.defaultCharacter()}
                        />
                    </div>
                    <div className="cols-start-3 cols-end-3">
                        <PrimaryButton
                            button_label={"Attack"}
                            on_click={this.fight.bind(this)}
                            disabled={
                                this.props.character.is_automation_running ||
                                this.props.character.is_dead ||
                                this.state.character_id === 0
                            }
                        />
                    </div>
                </div>
                {this.props.characters.length === 0 ? (
                    <p className="mt-4 text-sm text-center text-red-700 dark:text-red-500 w-2/3">
                        No one left to fight child. Best be on your way. Click:
                        Leave Fight.
                    </p>
                ) : null}
                <div className="md:ml-[-160px]">
                    <div
                        className={clsx("mt-4 mb-4 text-xs text-center", {
                            hidden: this.attackHidden(),
                        })}
                    >
                        <AttackButton
                            additional_css={"btn-attack"}
                            icon_class={"ra ra-sword"}
                            on_click={() => this.attack("attack")}
                            disabled={this.props.character.is_dead}
                        />
                        <AttackButton
                            additional_css={"btn-cast"}
                            icon_class={"ra ra-burning-book"}
                            on_click={() => this.attack("cast")}
                            disabled={this.props.character.is_dead}
                        />
                        <AttackButton
                            additional_css={"btn-cast-attack"}
                            icon_class={"ra ra-lightning-sword"}
                            on_click={() => this.attack("cast_and_attack")}
                            disabled={this.props.character.is_dead}
                        />
                        <AttackButton
                            additional_css={"btn-attack-cast"}
                            icon_class={"ra ra-lightning-sword"}
                            on_click={() => this.attack("attack_and_cast")}
                            disabled={this.props.character.is_dead}
                        />
                        <AttackButton
                            additional_css={"btn-defend"}
                            icon_class={"ra ra-round-shield"}
                            on_click={() => this.attack("defend")}
                            disabled={this.props.character.is_dead}
                        />
                        <a
                            href="/information/combat"
                            target="_blank"
                            className="ml-2"
                        >
                            Help <i className="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                    <div
                        className={clsx("mt-1 text-xs text-center ml-[-50px]", {
                            hidden: this.attackHidden(),
                        })}
                    >
                        <span className={"w-10 mr-4 ml-4"}>Atk</span>
                        <span className={"w-10 ml-6"}>Cast</span>
                        <span className={"w-10 ml-4"}>Cast & Atk</span>
                        <span className={"w-10 ml-2"}>Atk & Cast</span>
                        <span className={"w-10 ml-2"}>Defend</span>
                    </div>
                    {this.state.defender_max_health > 0 &&
                    this.props.characters.length > 0 ? (
                        <div
                            className={clsx("mb-4 max-w-md m-auto", {
                                "mt-4": this.attackHidden(),
                            })}
                        >
                            <HealthMeters
                                is_enemy={true}
                                name={this.defenderName()}
                                current_health={this.state.defender_health}
                                max_health={this.state.defender_max_health}
                            />
                            <HealthMeters
                                is_enemy={false}
                                name={this.props.character.name}
                                current_health={this.state.attacker_health}
                                max_health={this.state.attacker_max_health}
                            />
                            <div className="my-2">
                                <p className="text-red-500 dark:text-red-400 text-sm">
                                    {this.defenderName()} Elemental Atonement:{" "}
                                    {this.state.defender_atonement}
                                </p>
                                <p className="text-green-700 dark:text-green-400 text-sm">
                                    Your Elemental Atonement:{" "}
                                    {this.state.attacker_atonement}
                                </p>
                            </div>
                        </div>
                    ) : null}
                    {this.state.preforming_action ? (
                        <div className="w-1/2 ml-auto mr-auto">
                            <LoadingProgressBar />
                        </div>
                    ) : null}
                    <div className="italic text-center my-4">
                        <BattleMesages
                            is_small={this.props.is_small}
                            battle_messages={this.state.battle_messages}
                        />
                    </div>
                    <div className="text-center">
                        <DangerButton
                            button_label={"Leave Fight"}
                            on_click={this.props.manage_pvp}
                            additional_css={"mr-4"}
                            disabled={this.props.character.is_dead}
                        />
                        {this.props.character.is_dead ? (
                            <PrimaryButton
                                button_label={"Revive"}
                                on_click={this.revive.bind(this)}
                                disabled={!this.props.character.can_attack}
                            />
                        ) : null}
                    </div>
                </div>
            </div>
        );
    }
}
