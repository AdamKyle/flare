import React, { Fragment } from "react";
import DangerButton from "../../../components/ui/buttons/danger-button";
import Select from "react-select";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import SuccessButton from "../../../components/ui/buttons/success-button";
import InfoAlert from "../../../components/ui/alerts/simple-alerts/info-alert";
import WarningAlert from "../../../components/ui/alerts/simple-alerts/warning-alert";
import { match } from "ts-pattern";
import { startCase } from "lodash";

export default class ExplorationSection extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            monster_selected: null,
            time_selected: null,
            attack_type: null,
            delve_pack_size: null,
            move_down_monster_list: null,
            error_message: null,
            hide_exploration_help: false,
            show_delve_setup: false,
            show_exploration_setup: false,
        };
    }

    componentDidMount() {
        if (localStorage.getItem("hide-exploration-help") !== null) {
            this.setState({
                hide_exploration_help: true,
                show_delve_setup: true,
            });
        }
    }

    setMonsterToFight(data: any) {
        const foundMonster = this.props.monsters.filter(
            (monster: any) => monster.id === parseInt(data.value),
        );

        if (foundMonster.length > 0) {
            this.setState({
                monster_selected: foundMonster[0],
            });
        } else {
            this.setState({
                monster_selected: null,
            });
        }
    }

    monsterOptions() {
        let monsters = this.props.monsters.map((monster: any) => {
            return { label: monster.name, value: monster.id };
        });

        monsters.unshift({
            label: "Please Select",
            value: 0,
        });

        return monsters;
    }

    defaultSelectedMonster() {
        if (this.state.monster_selected !== null) {
            return {
                label: this.state.monster_selected.name,
                value: this.state.monster_selected.id,
            };
        }

        return {
            label: "Please Select Monster",
            value: "",
        };
    }

    setLengthOfTime(data: any) {
        this.setState({
            time_selected: data.value !== "" ? data.value : null,
        });
    }

    setAttackType(data: any) {
        this.setState({
            attack_type: data.value !== "" ? data.value : null,
        });
    }

    setDelvePackSize(data: any) {
        this.setState({
            delve_pack_size:
                data.value !== "" ? parseInt(data.value) || null : null,
        });
    }

    setMoveDownList(data: any) {
        this.setState({
            move_down_monster_list: data.value !== "" ? data.value : null,
        });
    }

    timeOptions() {
        return [
            {
                label: "1 Hour(s)",
                value: 1,
            },
            {
                label: "2 Hour(s)",
                value: 2,
            },
            {
                label: "4 Hour(s)",
                value: 4,
            },
            {
                label: "6 Hour(s)",
                value: 6,
            },
            {
                label: "8 Hour(s)",
                value: 8,
            },
        ];
    }

    attackTypes() {
        return [
            {
                label: "Attack",
                value: "attack",
            },
            {
                label: "Cast",
                value: "cast",
            },
            {
                label: "Attack and Cast",
                value: "attack_and_cast",
            },
            {
                label: "Cast and Attack",
                value: "cast_and_attack",
            },
            {
                label: "Defend",
                value: "defend",
            },
        ];
    }

    packSize() {
        return [
            {
                label: "1 Enemy",
                value: 1,
            },
            {
                label: "2 Enemies",
                value: "2",
            },
            {
                label: "5 Enemies",
                value: "5",
            },
            {
                label: "10 Enemies",
                value: "10",
            },
            {
                label: "20 Enemies",
                value: "20",
            },
            {
                label: "25 Enemies",
                value: "25",
            },
        ];
    }

    moveDownTheListEvery() {
        return [
            {
                label: "5 Levels",
                value: 5,
            },
            {
                label: "10 Levels",
                value: 10,
            },
            {
                label: "20 Levels",
                value: 20,
            },
        ];
    }

    defaultSelectedTime() {
        if (this.state.time_selected != null) {
            return [
                {
                    label: this.state.time_selected + " Hour(s)",
                    value: this.state.time_selected,
                },
            ];
        }

        return [
            {
                label: "Please select length of time",
                value: "",
            },
        ];
    }

    defaultAttackType() {
        if (this.state.attack_type !== null) {
            return {
                label: startCase(this.state.attack_type),
                value: this.state.attack_type,
            };
        }

        return {
            label: "Please select attack type",
            value: "",
        };
    }

    defaultDelvePackSize() {
        if (this.state.delve_pack_size !== null) {
            const label = this.state.delve_pack_size > 1 ? "Enemies" : "Enemy";

            return {
                label: this.state.delve_pack_size + " " + label,
                value: this.state.delve_pack_size,
            };
        }

        return {
            label: "Please select a pack size",
            value: "",
        };
    }

    defaultMoveDownList() {
        if (this.state.move_down_monster_list !== null) {
            return {
                label: this.state.move_down_monster_list + " levels",
                value: this.state.move_down_monster_list,
            };
        }

        return {
            label: "Move down the list (optional)",
            value: "",
        };
    }

    startExploration() {
        this.setState(
            {
                loading: true,
                error_message: null,
            },
            () => {
                new Ajax()
                    .setRoute(
                        "exploration/" + this.props.character.id + "/start",
                    )
                    .setParameters({
                        auto_attack_length: this.state.time_selected,
                        move_down_the_list_every:
                            this.state.move_down_monster_list,
                        selected_monster_id: this.state.monster_selected.id,
                        attack_type: this.state.attack_type,
                    })
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState({
                                loading: false,
                            });
                        },
                        (error: AxiosError) => {
                            this.setState({ loading: false });

                            if (typeof error.response !== "undefined") {
                                const response: AxiosResponse = error.response;

                                this.setState({
                                    error_message: response.data.message,
                                });
                            }
                        },
                    );
            },
        );
    }

    startDelveExploration() {
        this.setState(
            {
                loading: true,
                error_message: null,
            },
            () => {
                new Ajax()
                    .setRoute("delve/" + this.props.character.id + "/start")
                    .setParameters({
                        attack_type: this.state.attack_type,
                        pack_size: this.state.delve_pack_size,
                    })
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState({
                                loading: false,
                            });
                        },
                        (error: AxiosError) => {
                            this.setState({ loading: false });

                            if (typeof error.response !== "undefined") {
                                const response: AxiosResponse = error.response;

                                this.setState({
                                    error_message: response.data.message,
                                });
                            }
                        },
                    );
            },
        );
    }

    stopExploration() {
        this.setState(
            {
                loading: true,
                error_message: null,
            },
            () => {
                new Ajax()
                    .setRoute(
                        "exploration/" + this.props.character.id + "/stop",
                    )
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState({
                                loading: false,
                            });
                        },
                        (error: AxiosError) => {},
                    );
            },
        );
    }

    stopDelveExploration() {
        this.setState(
            {
                loading: true,
                error_message: null,
            },
            () => {
                new Ajax()
                    .setRoute("delve/" + this.props.character.id + "/stop")
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState({
                                loading: false,
                            });
                        },
                        (error: AxiosError) => {},
                    );
            },
        );
    }

    hideExplorationHelp() {
        localStorage.setItem("hide-exploration-help", "true");

        this.setState({
            hide_exploration_help: true,
        });
    }

    closeDelve() {
        if (this.state.hide_exploration_help) {
            return this.props.manage_exploration();
        }

        this.setState({
            show_delve_setup: false,
        });
    }

    openDelveSetUp() {
        this.setState({
            show_delve_setup: true,
        });
    }

    closeExploration() {
        if (!this.state.show_exploration_setup) {
            this.props.manage_exploration();

            return;
        }

        this.setState({
            show_exploration_setup: false,
        });
    }

    renderAudotmationIsRunning() {
        if (this.props.character.is_delve_running) {
            return (
                <Fragment>
                    <div className="mb-4 lg:ml-[120px] text-center lg:text-left">
                        Delve is running. You can cancel it below.{" "}
                        <a href="/information/automation" target="_blank">
                            See Delve Help{" "}
                            <i className="fas fa-external-link-alt"></i>
                        </a>{" "}
                        for more details.
                    </div>

                    {this.state.loading ? <LoadingProgressBar /> : null}

                    <div className="text-center">
                        <DangerButton
                            button_label={"Stop Delve"}
                            on_click={this.stopDelveExploration.bind(this)}
                            disabled={this.state.loading}
                            additional_css={"mr-2 mb-4"}
                        />
                        <PrimaryButton
                            button_label={"Close Delve"}
                            on_click={this.closeExploration.bind(this)}
                            disabled={this.state.loading}
                        />
                    </div>
                </Fragment>
            );
        }

        if (this.props.character.is_automation_running) {
            return (
                <Fragment>
                    <div className="mb-4 lg:ml-[120px] text-center lg:text-left">
                        Automation is running. You can cancel it below.{" "}
                        <a href="/information/automation" target="_blank">
                            See Exploration Help{" "}
                            <i className="fas fa-external-link-alt"></i>
                        </a>{" "}
                        for more details.
                    </div>

                    {this.state.loading ? <LoadingProgressBar /> : null}

                    <div className="text-center">
                        <DangerButton
                            button_label={"Stop Exploration"}
                            on_click={this.stopExploration.bind(this)}
                            disabled={this.state.loading}
                            additional_css={"mr-2 mb-4"}
                        />
                        <PrimaryButton
                            button_label={"Close Exploration"}
                            on_click={this.closeExploration.bind(this)}
                            disabled={this.state.loading}
                        />
                    </div>
                </Fragment>
            );
        }
    }

    renderExplorationSection() {
        return (
            <Fragment>
                <div className="mt-2 grid lg:grid-cols-3 gap-2 lg:ml-[120px]">
                    <div className="cols-start-1 col-span-2">
                        <div className="mb-3">
                            <Select
                                onChange={this.setMonsterToFight.bind(this)}
                                options={this.monsterOptions()}
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
                                value={this.defaultSelectedMonster()}
                            />
                        </div>
                        <div className="mb-3">
                            <Select
                                onChange={this.setLengthOfTime.bind(this)}
                                options={this.timeOptions()}
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
                                value={this.defaultSelectedTime()}
                            />
                        </div>
                        <div className="mb-3">
                            <Select
                                onChange={this.setMoveDownList.bind(this)}
                                options={this.moveDownTheListEvery()}
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
                                value={this.defaultMoveDownList()}
                            />
                        </div>
                        <div>
                            <Select
                                onChange={this.setAttackType.bind(this)}
                                options={this.attackTypes()}
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
                                value={this.defaultAttackType()}
                            />
                        </div>
                    </div>
                </div>

                <div className={"lg:text-center mt-3 mb-3"}>
                    <PrimaryButton
                        button_label={"Explore"}
                        on_click={this.startExploration.bind(this)}
                        disabled={
                            this.state.monster_selected === null ||
                            this.state.time_selected === null ||
                            this.state.attack_type === null ||
                            this.state.loading ||
                            this.props.character.is_dead ||
                            !this.props.character.can_attack
                        }
                        additional_css={"mr-2 mb-4"}
                    />
                    <DangerButton
                        button_label={"Close"}
                        on_click={this.closeExploration.bind(this)}
                        disabled={this.state.loading}
                    />

                    {this.state.loading ? (
                        <div className="w-1/2 ml-auto mr-auto">
                            <LoadingProgressBar />
                        </div>
                    ) : null}

                    {this.state.error_message !== null ? (
                        <div className="w-1/2 ml-auto mr-auto mt-4">
                            <DangerAlert>
                                {this.state.error_message}
                            </DangerAlert>
                        </div>
                    ) : null}

                    <div className="relative top-[24px] italic">
                        <p>
                            For more help please the{" "}
                            <a href="/information/exploration" target="_blank">
                                Exploration{" "}
                                <i className="fas fa-external-link-alt"></i>
                            </a>{" "}
                            help docs.
                        </p>
                    </div>
                </div>
            </Fragment>
        );
    }

    renderExplorationHelp() {
        if (this.state.hide_exploration_help) {
            return null;
        }

        return (
            <InfoAlert
                additional_css={"w-full"}
                close_alert={this.hideExplorationHelp.bind(this)}
            >
                <h2>Delve</h2>
                <p className={"my-2"}>
                    <strong>Delve</strong> is an exploration mechanic where
                    specific locations pit you against a random monster each
                    turn. When you kill the creature, the next turn selects a
                    new monster and increases its stats by a percentage. This
                    percentage increases with each successful kill until you
                    eventually die. It is not about surviving for 8 hours. It is
                    about seeing how far you can make it.
                </p>
                <p className={"mb-2"}>
                    The longer you last, the better the rewards will be. You can
                    read more about{" "}
                    <a href="/information/delve" target="_blank">
                        Delve <i className="fas fa-external-link-alt"></i>
                    </a>{" "}
                    in the help docs.
                </p>
            </InfoAlert>
        );
    }

    renderPackSizeAlert(packSize: number) {
        if (!this.state.delve_pack_size) {
            return null;
        }

        const xpBoost = match(packSize)
            .with(1, () => "+0%")
            .with(5, () => "+100%")
            .with(10, () => "+150%")
            .with(20, () => "+175%")
            .with(25, () => "+200%")
            .otherwise(() => "+0%");

        return (
            <WarningAlert>
                Selecting this pack size will give you a bonus of{" "}
                <strong>{xpBoost} XP</strong> per monster fought. This bonus is
                applied to the xp after all other bonuses are applied and is per
                fight.
            </WarningAlert>
        );
    }

    renderPackSizeOption() {
        if (!this.props.character.can_set_delve_pack) {
            return null;
        }

        return (
            <div className="mt-2 grid lg:grid-cols-1 gap-2 lg:ml-[120px]">
                <Select
                    onChange={this.setDelvePackSize.bind(this)}
                    options={this.packSize()}
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
                    value={this.defaultDelvePackSize()}
                />
                {this.renderPackSizeAlert(this.state.delve_pack_size)}
            </div>
        );
    }

    renderSetUpDelveExploration() {
        return (
            <Fragment>
                <div className="mt-2 grid lg:grid-cols-1 gap-2 lg:ml-[120px]">
                    <Select
                        onChange={this.setAttackType.bind(this)}
                        options={this.attackTypes()}
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
                        value={this.defaultAttackType()}
                    />
                </div>

                {this.renderPackSizeOption()}

                <div className={"lg:text-center mt-3 mb-3"}>
                    <PrimaryButton
                        button_label={"Delve"}
                        on_click={this.startDelveExploration.bind(this)}
                        disabled={
                            this.state.attack_type === null ||
                            this.state.loading ||
                            this.props.character.is_dead ||
                            !this.props.character.can_attack
                        }
                        additional_css={"mr-2 mb-4"}
                    />
                    <DangerButton
                        button_label={"Close"}
                        on_click={this.closeDelve.bind(this)}
                        disabled={this.state.loading}
                    />

                    {this.state.loading ? (
                        <div className="w-1/2 ml-auto mr-auto">
                            <LoadingProgressBar />
                        </div>
                    ) : null}

                    {this.state.error_message !== null ? (
                        <div className="w-1/2 ml-auto mr-auto mt-4">
                            <DangerAlert>
                                {this.state.error_message}
                            </DangerAlert>
                        </div>
                    ) : null}

                    <div className="relative top-[24px] italic">
                        <p>
                            For more help please see the{" "}
                            <a href="/information/exploration" target="_blank">
                                Delve{" "}
                                <i className="fas fa-external-link-alt"></i>
                            </a>{" "}
                            help docs.
                        </p>
                    </div>
                </div>
            </Fragment>
        );
    }

    render() {
        if (
            this.props.character.is_automation_running ||
            this.props.character.is_delve_running
        ) {
            return this.renderAudotmationIsRunning();
        }

        if (!this.props.character.is_at_delve_location) {
            return this.renderExplorationSection();
        }

        if (this.state.show_delve_setup) {
            return this.renderSetUpDelveExploration();
        }

        if (this.state.show_exploration_setup) {
            return this.renderExplorationSection();
        }

        return (
            <div className={"w-2/3 mx-auto"}>
                {this.renderExplorationHelp()}
                <div>
                    <SuccessButton
                        button_label={"Setup Delve"}
                        on_click={this.openDelveSetUp.bind(this)}
                        disabled={
                            this.props.character.is_dead ||
                            !this.props.character.can_attack
                        }
                        additional_css={"w-full my-2"}
                    />
                </div>
                <div>
                    <DangerButton
                        button_label={"Close"}
                        on_click={this.props.manage_exploration}
                        additional_css={"w-full my-2"}
                    />
                </div>
            </div>
        );
    }
}
