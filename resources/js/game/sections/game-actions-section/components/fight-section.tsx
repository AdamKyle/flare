import React from "react";
import AttackButton from "../../../components/ui/buttons/attack-button";
import clsx from "clsx";
import HealthMeters from "./health-meters";
import FightSectionProps from "./types/fight-section-props";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import FightSectionState from "./types/fight-section-state";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import BattleMesages from "./fight-section/battle-mesages";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import PrimaryLinkButton from "../../../components/ui/buttons/primary-link-button";
import RaidElementInfo from "./fight-section/modals/raid-elemental-info";

export default class FightSection extends React.Component<
    FightSectionProps,
    FightSectionState
> {
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
            setting_up_regular_fight: false,
            processing_regular_fight: false,
            show_clear_message: true,
            open_elemental_atonement: false,
            error_message: "",
        };
    }

    componentDidMount() {
        this.setUpBattle();
    }

    componentDidUpdate() {
        if (
            this.props.monster_to_fight.id !== this.state.monster_to_fight_id &&
            this.state.monster_to_fight_id !== 0
        ) {
            this.setState({
                monster_to_fight_id: this.props.monster_to_fight.id,
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
            this.setState(
                {
                    character_current_health: this.props.character?.health,
                    character_max_health: this.props.character?.health,
                    battle_messages: [],
                },
                () => {
                    this.props.reset_revived();
                },
            );
        }
    }

    setUpBattle() {
        if (this.props.character == null) {
            return;
        }

        this.setState(
            {
                setting_up_regular_fight: true,
                show_clear_message: true,
                error_message: "",
            },
            () => {
                new Ajax()
                    .setRoute(
                        "setup-monster-fight/" +
                            this.props.character.id +
                            "/" +
                            this.props.monster_to_fight.id,
                    )
                    .setParameters({ attack_type: "attack" })
                    .doAjaxCall(
                        "get",
                        (result: AxiosResponse) => {
                            this.setState({
                                battle_messages: result.data.opening_messages,
                                character_current_health:
                                    result.data.health.current_character_health,
                                character_max_health:
                                    result.data.health.max_character_health,
                                monster_current_health:
                                    result.data.health.current_monster_health,
                                monster_max_health:
                                    result.data.health.max_monster_health,
                                monster_to_fight_id: result.data.monster.id,
                                setting_up_regular_fight: false,
                                monster_to_fight: result.data.monster,
                            });
                        },
                        (error: AxiosError) => {
                            if (typeof error.response !== "undefined") {
                                const response: AxiosResponse = error.response;

                                this.setState({
                                    error_message: response.data.message,
                                    setting_up_regular_fight: false,
                                });
                            }
                        },
                    );
            },
        );
    }

    attack(attackType: string) {
        this.setState(
            {
                processing_regular_fight: true,
                error_message: "",
            },
            () => {
                new Ajax()
                    .setRoute("monster-fight/" + this.props.character.id)
                    .setParameters({ attack_type: attackType })
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState({
                                battle_messages: result.data.messages,
                                character_current_health:
                                    result.data.health
                                        .current_character_health < 0
                                        ? 0
                                        : result.data.health
                                              .current_character_health,
                                monster_current_health:
                                    result.data.health.current_monster_health <
                                    0
                                        ? 0
                                        : result.data.health
                                              .current_monster_health,
                                processing_regular_fight: false,
                            });
                        },
                        (error: AxiosError) => {
                            if (typeof error.response !== "undefined") {
                                const response: AxiosResponse = error.response;

                                this.setState({
                                    error_message: response.data.message,
                                    processing_regular_fight: false,
                                });
                            }
                        },
                    );
            },
        );
    }

    attackButtonDisabled() {
        if (typeof this.state.character_current_health === "undefined") {
            return true;
        }

        return (
            this.state.monster_current_health <= 0 ||
            this.state.character_current_health <= 0 ||
            this.props.character?.is_dead ||
            !this.props.character?.can_attack
        );
    }

    clearBattleMessages() {
        this.setState({
            battle_messages: [],
            monster_max_health:
                this.state.monster_current_health <= 0
                    ? 0
                    : this.state.monster_max_health,
            show_clear_message: this.state.monster_current_health > 0,
        });
    }

    manageElementalAtonement() {
        this.setState({
            open_elemental_atonement: !this.state.open_elemental_atonement,
        });
    }

    render() {
        if (this.state.setting_up_regular_fight) {
            return (
                <div className="flex items-center justify-center my-4">
                    <LoadingProgressBar />
                </div>
            );
        }

        if (this.state.error_message !== "") {
            return (
                <div className="ml-auto mr-auto my-4 md:max-w-3/4">
                    <DangerAlert>{this.state.error_message}</DangerAlert>
                </div>
            );
        }

        return (
            <div className="m-auto max-w-[75%]">
                {this.state.monster_to_fight?.highest_element !== "UNKNOWN" && (
                    <div className="flex items-center justify-center mt-4 mb-4">
                        <div className="text-center">
                            <PrimaryLinkButton
                                button_label={"Elemental Atonement Info"}
                                on_click={this.manageElementalAtonement.bind(
                                    this,
                                )}
                            />
                        </div>
                    </div>
                )}

                <div
                    className={clsx("mt-4 mb-4 text-center", {
                        hidden: this.attackButtonDisabled(),
                    })}
                >
                    <div className="flex justify-center space-x-2 md:space-x-2 lg:space-x-3">
                        <div className="flex flex-col items-center">
                            <AttackButton
                                is_small={this.props.is_small}
                                type={"Atk"}
                                additional_css={"btn-attack"}
                                icon_class={"ra ra-sword"}
                                on_click={() => this.attack("attack")}
                                disabled={this.attackButtonDisabled()}
                            />
                            <span className="w-full text-center">Atk</span>
                        </div>
                        <div className="flex flex-col items-center">
                            <AttackButton
                                is_small={this.props.is_small}
                                type={"Cast"}
                                additional_css={"btn-cast"}
                                icon_class={"ra ra-burning-book"}
                                on_click={() => this.attack("cast")}
                                disabled={this.attackButtonDisabled()}
                            />
                            <span className="w-full text-center">Cast</span>
                        </div>
                        <div className="flex flex-col items-center">
                            <AttackButton
                                is_small={this.props.is_small}
                                type={"Cast & Atk"}
                                additional_css={"btn-cast-attack"}
                                icon_class={"ra ra-lightning-sword"}
                                on_click={() => this.attack("cast_and_attack")}
                                disabled={this.attackButtonDisabled()}
                            />
                            <span className="w-full text-center">
                                Cast & Atk
                            </span>
                        </div>
                        <div className="flex flex-col items-center">
                            <AttackButton
                                is_small={this.props.is_small}
                                type={"Atk & Cast"}
                                additional_css={"btn-attack-cast"}
                                icon_class={"ra ra-lightning-sword"}
                                on_click={() => this.attack("attack_and_cast")}
                                disabled={this.attackButtonDisabled()}
                            />
                            <span className="w-full text-center">
                                Atk & Cast
                            </span>
                        </div>
                        <div className="flex flex-col items-center">
                            <AttackButton
                                is_small={this.props.is_small}
                                type={"Defend"}
                                additional_css={"btn-defend"}
                                icon_class={"ra ra-round-shield"}
                                on_click={() => this.attack("defend")}
                                disabled={this.attackButtonDisabled()}
                            />
                            <span className="w-full text-center">Defend</span>
                        </div>
                    </div>
                </div>

                {(this.state.processing_rank_battle ||
                    this.state.processing_regular_fight) && (
                    <div className="w-1/2 mx-auto">
                        <LoadingProgressBar />
                    </div>
                )}

                {this.attackButtonDisabled() &&
                    this.state.show_clear_message && (
                        <div className="text-center mt-4">
                            <button
                                onClick={this.clearBattleMessages.bind(this)}
                                className="text-red-500 dark:text-red-400 underline hover:text-red-600 dark:hover:text-red-500"
                            >
                                Clear
                            </button>
                        </div>
                    )}

                {this.state.monster_max_health > 0 && this.props.character && (
                    <div className="mb-4 max-w-md mx-auto mt-4">
                        <HealthMeters
                            is_enemy={true}
                            name={this.props.monster_to_fight.name}
                            current_health={this.state.monster_current_health}
                            max_health={this.state.monster_max_health}
                        />
                        <HealthMeters
                            is_enemy={false}
                            name={this.props.character.name}
                            current_health={this.state.character_current_health}
                            max_health={this.state.character_max_health}
                        />
                    </div>
                )}

                {this.state.open_elemental_atonement &&
                    this.state.monster_to_fight && (
                        <RaidElementInfo
                            element_atonements={
                                this.state.monster_to_fight.elemental_atonement
                            }
                            highest_element={
                                this.state.monster_to_fight.highest_element
                            }
                            monster_name={this.state.monster_to_fight.name}
                            is_open={this.state.open_elemental_atonement}
                            manage_modal={this.manageElementalAtonement.bind(
                                this,
                            )}
                        />
                    )}

                <div className="italic text-center">
                    <BattleMesages
                        battle_messages={this.state.battle_messages}
                        is_small={this.props.is_small}
                    />
                </div>
            </div>
        );
    }
}
