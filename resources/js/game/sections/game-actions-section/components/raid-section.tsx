import React, { Fragment } from "react";
import RaidSelectionProps from "./types/raid-selection-props";
import RaidMonsterType from "../../../lib/game/types/actions/monster/raid-monster-type";
import RaidSelectionState from "./types/raid-selection-state";
import Ajax from "../../..//lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import MonsterSelection from "./fight-section/monster-selection";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import RaidFight from "./raid-fight";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import RaidElementInfo from "./fight-section/modals/raid-elemental-info";

export default class RaidSection extends React.Component<
    RaidSelectionProps,
    RaidSelectionState
> {
    private updateRaidBosshealth: any;

    private characterRevive: any;

    constructor(props: RaidSelectionProps) {
        super(props);

        this.state = {
            is_loading: false,
            is_fighting: false,
            character_current_health: 0,
            character_max_health: 0,
            monster_current_health: 0,
            monster_max_health: 0,
            selected_raid_monster_id: 0,
            monster_name: "",
            revived: false,
            raid_boss_attacks_left: 0,
            is_raid_boss: false,
            open_elemental_atonement: false,
            elemental_atonement: {},
            highest_element: null,
            update_raid_fight: false,
        };

        // @ts-ignore
        this.updateRaidBosshealth = Echo.join("update-raid-boss-health-attack");

        // @ts-ignore
        this.characterRevive = Echo.private(
            "character-revive-" + this.props.user_id
        );
    }

    componentDidMount(): void {
        // @ts-ignore
        this.updateRaidBosshealth.listen(
            "Game.Battle.Events.UpdateRaidBossHealth",
            (event: any) => {
                if (event.raidBossId === this.state.selected_raid_monster_id) {
                    this.setState({
                        monster_current_health: event.raidBossHealth,
                    });
                }
            }
        );

        // @ts-ignore
        this.characterRevive.listen(
            "Game.Battle.Events.CharacterRevive",
            (event: { health: number }) => {
                this.setState({
                    character_current_health: event.health,
                });
            }
        );
    }

    componentDidUpdate(
        prevProps: Readonly<RaidSelectionProps>
    ): void {
        if (
            this.props.raid_monsters.length !==
                prevProps.raid_monsters.length &&
            this.state.monster_name !== ""
        ) {
            this.setState({
                monster_name: "",
            });
        }
    }

    buildRaidMonsterSelection() {
        if (this.props.raid_monsters.length === 0) {
            return [
                {
                    label: "",
                    value: 0,
                },
            ];
        }

        const raidMonsters = this.props.raid_monsters.map(
            (raidMonster: RaidMonsterType) => {
                return {
                    label: raidMonster.name,
                    value: raidMonster.id,
                };
            }
        );

        raidMonsters.unshift({
            label: "Please select raid monster",
            value: 0,
        });

        return raidMonsters;
    }

    defaultMonsterSelected(): { label: string; value: number }[] {
        if (this.state.selected_raid_monster_id === 0) {
            return [
                {
                    label: "Please select raid monster",
                    value: 0,
                },
            ];
        }

        const raidMonster = this.props.raid_monsters.find(
            (raidMonster: RaidMonsterType) => {
                if (raidMonster.id === this.state.selected_raid_monster_id) {
                    return raidMonster;
                }
            }
        );

        if (typeof raidMonster === "undefined") {
            return [
                {
                    label: "Please select raid monster",
                    value: 0,
                },
            ];
        }

        return [
            {
                label: raidMonster.name,
                value: raidMonster.id,
            },
        ];
    }

    setMonsterToFight(data: any) {
        if (data.value === 0) {
            return;
        }

        this.setState({
            selected_raid_monster_id: data.value,
        });
    }

    initializeMonsterForAttack() {
        if (this.state.selected_raid_monster_id === 0) {
            return;
        }

        const self = this;

        this.setState(
            {
                is_loading: true,
            },
            () => {
                new Ajax()
                    .setRoute(
                        "raid-fight-participation/" +
                            this.props.character_id +
                            "/" +
                            this.state.selected_raid_monster_id
                    )
                    .doAjaxCall(
                        "get",
                        (result: AxiosResponse) => {
                            this.setState({
                                is_loading: false,
                                character_current_health:
                                    result.data.character_max_health,
                                character_max_health:
                                    result.data.character_current_health,
                                monster_max_health:
                                    result.data.monster_max_health,
                                monster_current_health:
                                    result.data.monster_current_health,
                                monster_name: self.fetchRaidMonsterName(),
                                raid_boss_attacks_left:
                                    result.data.attacks_left,
                                is_raid_boss: result.data.is_raid_boss,
                                elemental_atonement:
                                    result.data.elemental_atonemnt,
                                highest_element: result.data.highest_element,
                                update_raid_fight: true,
                            });
                        },
                        (error: AxiosError) => {
                            this.setState({
                                is_loading: false,
                            });

                            console.error(error);
                        }
                    );
            }
        );
    }

    resetUpdate(): void {
        this.setState({
            update_raid_fight: false,
        })
    }

    fetchRaidMonsterName(): string {
        if (this.props.raid_monsters.length <= 0) {
            return "ERROR.";
        }

        const raidMonster = this.props.raid_monsters.find(
            (raidMonster: RaidMonsterType) => {
                if (raidMonster.id === this.state.selected_raid_monster_id) {
                    return raidMonster;
                }
            }
        );

        if (typeof raidMonster === "undefined") {
            return "ERROR.";
        }

        return raidMonster.name;
    }

    revive() {
        this.setState(
            {
                is_fighting: true,
            },
            () => {
                new Ajax()
                    .setRoute("battle-revive/" + this.props.character_id)
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState({
                                is_fighting: false,
                                revived: true,
                            });
                        },
                        (error: AxiosError) => {
                            this.setState({ is_fighting: false });

                            console.error(error);
                        }
                    );
            }
        );
    }

    resetRevived(): void {
        this.setState({
            revived: false,
        });
    }

    attackButtonDisabled() {
        return (
            this.props.is_dead ||
            !this.props.can_attack ||
            this.state.selected_raid_monster_id === 0
        );
    }

    manageAtonementModal() {
        this.setState({
            open_elemental_atonement: !this.state.open_elemental_atonement,
        });
    }

    render() {
        return (
            <Fragment>
                <MonsterSelection
                    set_monster_to_fight={this.setMonsterToFight.bind(this)}
                    monsters={this.buildRaidMonsterSelection()}
                    default_monster={this.defaultMonsterSelected()}
                    attack={this.initializeMonsterForAttack.bind(this)}
                    is_attack_disabled={this.attackButtonDisabled()}
                    close_monster_section={this.props.close_monster_section}
                />

                {this.state.is_loading || this.state.is_fighting ? (
                    <div className="flex items-center justify-center">
                        <div className="w-[50%]">
                            <LoadingProgressBar />
                        </div>
                    </div>
                ) : null}

                {this.props.is_dead && this.state.monster_name === "" ? (
                    <div className="text-center mr-4 mt-4">
                        <PrimaryButton
                            button_label={"Revive"}
                            on_click={this.revive.bind(this)}
                            disabled={!this.props.can_attack}
                        />
                    </div>
                ) : null}

                {this.props.children}

                {this.state.monster_name !== "" ? (
                    <RaidFight
                        user_id={this.props.user_id}
                        character_current_health={
                            this.state.character_current_health
                        }
                        character_max_health={this.state.character_max_health}
                        monster_current_health={
                            this.state.monster_current_health
                        }
                        monster_max_health={this.state.monster_max_health}
                        can_attack={this.props.can_attack}
                        is_dead={this.props.is_dead}
                        monster_name={this.state.monster_name}
                        monster_id={this.state.selected_raid_monster_id}
                        is_small={this.props.is_small}
                        character_name={this.props.character_name}
                        character_id={this.props.character_id}
                        revive={this.revive.bind(this)}
                        reset_revived={this.resetRevived.bind(this)}
                        revived={this.state.revived}
                        initial_attacks_left={this.state.raid_boss_attacks_left}
                        is_raid_boss={this.state.is_raid_boss}
                        manage_elemental_atonement_modal={this.manageAtonementModal.bind(
                            this
                        )}
                        update_raid_fight={this.state.update_raid_fight}
                        reset_update={this.resetUpdate.bind(this)}
                    />
                ) : null}

                {this.state.open_elemental_atonement ? (
                    <RaidElementInfo
                        element_atonements={this.state.elemental_atonement}
                        highest_element={this.state.highest_element}
                        monster_name={this.state.monster_name}
                        is_open={this.state.open_elemental_atonement}
                        manage_modal={this.manageAtonementModal.bind(this)}
                    />
                ) : null}
            </Fragment>
        );
    }
}
