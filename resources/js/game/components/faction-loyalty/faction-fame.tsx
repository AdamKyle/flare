import { AxiosError, AxiosResponse } from "axios";
import React from "react";
import { Channel } from "laravel-echo";
import DangerAlert from "../../components/ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../components/ui/alerts/simple-alerts/success-alert";
import WarningAlert from "../../components/ui/alerts/simple-alerts/warning-alert";
import DangerOutlineButton from "../../components/ui/buttons/danger-outline-button";
import PrimaryOutlineButton from "../../components/ui/buttons/primary-outline-button";
import DropDown from "../../components/ui/drop-down/drop-down";
import LoadingProgressBar from "../../components/ui/progress-bars/loading-progress-bar";
import Ajax from "../../lib/ajax/ajax";
import ActionsTimers from "../timers/actions-timers";
import { updateTimers } from "../../lib/ajax/update-timers";
import {
    FactionLoyalty,
    FactionLoyaltyNpc,
    FactionLoyaltyWarningNotice,
    FameTasks,
} from "./deffinitions/faction-loaylaty";
import FactionNpcSection from "./faction-npc-section";
import FactionNpcTasks from "./faction-npc-tasks";
import FactionLoyaltyAutomation from "./faction-loyalty-automation";
import FactionLoyaltyProps from "./types/faction-loyalty-props";
import FactionLoyaltyState, {
    FactionLoyaltyNpcListItem,
} from "./types/faction-loyalty-state";
import FactionLoyaltyListeners from "./event-listeners/faction-loyalty-listeners";
import { serviceContainer } from "../../lib/containers/core-container";

declare const Echo: {
    private: (channel: string) => Channel;
};

interface AutomationTimeOutEvent {
    forLength: number;
}

export default class FactionFame extends React.Component<
    FactionLoyaltyProps,
    FactionLoyaltyState
> {
    private factionLoyaltyListeners: FactionLoyaltyListeners;

    private automationTimeOut: Channel;

    constructor(props: FactionLoyaltyProps) {
        super(props);

        this.state = {
            is_loading: true,
            is_processing: false,
            selected_npc: null,
            error_message: null,
            success_message: null,
            npcs: [],
            game_map_name: null,
            faction_loyalty: null,
            selected_faction_loyalty_npc: null,
            attack_type: "attack",
            show_automation_screen: false,
            is_faction_loyalty_automation_running:
                this.props.is_faction_loyalty_automation_running,
            automation_time_out: 0,
        };

        this.factionLoyaltyListeners = serviceContainer().fetch(
            FactionLoyaltyListeners,
        );
        this.factionLoyaltyListeners.initialize(this, this.props.user_id);
        this.factionLoyaltyListeners.register();

        this.automationTimeOut = Echo.private(
            "automation-timeout-" + this.props.user_id,
        );
    }

    componentDidMount() {
        new Ajax()
            .setRoute("faction-loyalty/" + this.props.character_id)
            .doAjaxCall(
                "get",
                (result: AxiosResponse) => {
                    this.setState(
                        {
                            is_loading: false,
                            npcs: result.data.npcs,
                            game_map_name: result.data.map_name,
                            faction_loyalty: result.data.faction_loyalty,
                            attack_type: this.normalizeAttackType(
                                result.data.attack_type,
                            ),
                        },
                        () => {
                            this.setInitialSelectedFactionInfo(
                                result.data.faction_loyalty,
                                result.data.npcs,
                            );
                        },
                    );
                },
                (error: AxiosError) => {
                    this.setState({ is_loading: false });

                    if (error.response) {
                        const response: AxiosResponse = error.response;

                        this.setState({
                            error_message: response.data.message,
                        });
                    }
                },
            );

        this.factionLoyaltyListeners.listen();
        this.automationTimeOut.listen(
            "Game.Automation.Events.AutomationTimeOut",
            (event: AutomationTimeOutEvent) => {
                this.setState({
                    automation_time_out: event.forLength,
                });
            },
        );

        if (this.isFactionLoyaltyAutomationRunning()) {
            updateTimers(this.props.character_id);
        }
    }

    componentDidUpdate(previousProps: FactionLoyaltyProps): void {
        if (
            previousProps.is_faction_loyalty_automation_running !==
            this.props.is_faction_loyalty_automation_running
        ) {
            this.setState({
                is_faction_loyalty_automation_running:
                    this.props.is_faction_loyalty_automation_running,
            });
        }

        if (
            previousProps.faction_loyalty_warning_notices !==
            this.props.faction_loyalty_warning_notices
        ) {
            this.applyWarningNotices(
                this.props.faction_loyalty_warning_notices,
            );
        }
    }

    manageAssistingNpc(isHelping: boolean) {
        if (!this.state.selected_faction_loyalty_npc) {
            return;
        }

        if (this.isAnyFactionLoyaltyActionBlocked()) {
            return;
        }

        this.setState(
            {
                error_message: null,
                is_processing: true,
            },
            () => {
                if (!this.state.selected_faction_loyalty_npc) {
                    this.setState({
                        error_message: null,
                        is_processing: false,
                    });

                    return;
                }

                new Ajax()
                    .setRoute(
                        "faction-loyalty/" +
                            (isHelping ? "stop-assisting" : "assist") +
                            "/" +
                            this.props.character_id +
                            "/" +
                            this.state.selected_faction_loyalty_npc.id,
                    )
                    .doAjaxCall("post", (result: AxiosResponse) => {
                        this.setState(
                            {
                                is_processing: false,
                                success_message: result.data.message,
                                faction_loyalty: result.data.faction_loyalty,
                            },
                            () => {
                                this.setInitialSelectedFactionInfo(
                                    result.data.faction_loyalty,
                                    this.state.npcs,
                                );
                            },
                        );
                    });
            },
        );
    }

    setInitialSelectedFactionInfo(
        factionLoyalty: FactionLoyalty,
        npcs: FactionLoyaltyNpcListItem[],
    ) {
        const hasWarning = this.hasWarningNotice(factionLoyalty);
        const warningNotices = this.getWarningNotices(factionLoyalty);
        let helpingNpc = factionLoyalty.faction_loyalty_npcs.filter(
            (factionLoyaltyNpc: FactionLoyaltyNpc) => {
                return factionLoyaltyNpc.currently_helping;
            },
        );

        if (helpingNpc.length === 0) {
            helpingNpc = factionLoyalty.faction_loyalty_npcs.filter(
                (factionLoyaltyNpc: FactionLoyaltyNpc) => {
                    return factionLoyaltyNpc.npc_id === npcs[0].id;
                },
            );

            this.setState({
                selected_npc: npcs[0],
                selected_faction_loyalty_npc: helpingNpc[0],
            });

            this.props.update_faction_action_tasks(null);
            this.props.update_faction_loyalty_warning(
                hasWarning,
                warningNotices,
            );

            return;
        }

        const factionLoyaltyNpcHelping = helpingNpc[0];

        this.setState({
            selected_npc: npcs.filter((npc: FactionLoyaltyNpcListItem) => {
                return npc.id === factionLoyaltyNpcHelping.npc_id;
            })[0],
            selected_faction_loyalty_npc: factionLoyaltyNpcHelping,
        });
        this.props.update_faction_loyalty_warning(hasWarning, warningNotices);

        this.props.update_faction_action_tasks(
            factionLoyaltyNpcHelping.faction_loyalty_npc_tasks.fame_tasks.filter(
                (fameTasks: FameTasks) => {
                    return fameTasks.type !== "bounty";
                },
            ),
        );

        return helpingNpc[0];
    }

    updateWarningNotice(
        hasWarning: boolean,
        warningNotices: FactionLoyaltyWarningNotice[],
    ): void {
        this.applyWarningNotices(warningNotices);
        this.props.update_faction_loyalty_warning(hasWarning, warningNotices);
    }

    applyWarningNotices(warningNotices: FactionLoyaltyWarningNotice[]): void {
        if (
            !this.state.faction_loyalty ||
            !this.state.selected_faction_loyalty_npc
        ) {
            return;
        }

        const selectedFactionLoyaltyNpcId =
            this.state.selected_faction_loyalty_npc.id;
        const updatedFactionLoyalty = {
            ...this.state.faction_loyalty,
            faction_loyalty_npcs:
                this.state.faction_loyalty.faction_loyalty_npcs.map(
                    (factionLoyaltyNpc: FactionLoyaltyNpc) => {
                        return {
                            ...factionLoyaltyNpc,
                            faction_loyalty_warning_notice:
                                warningNotices[0] ?? null,
                            faction_loyalty_warning_notices: warningNotices,
                        };
                    },
                ),
        };
        const selectedFactionLoyaltyNpc =
            updatedFactionLoyalty.faction_loyalty_npcs.find(
                (factionLoyaltyNpc: FactionLoyaltyNpc) => {
                    return factionLoyaltyNpc.id === selectedFactionLoyaltyNpcId;
                },
            ) ?? null;

        this.setState({
            faction_loyalty: updatedFactionLoyalty,
            selected_faction_loyalty_npc: selectedFactionLoyaltyNpc,
        });
    }

    hasWarningNotice(factionLoyalty: FactionLoyalty): boolean {
        if (this.props.has_faction_loyalty_warning) {
            return true;
        }

        return this.getWarningNotices(factionLoyalty).length > 0;
    }

    getWarningNotices(
        factionLoyalty: FactionLoyalty,
    ): FactionLoyaltyWarningNotice[] {
        if (this.props.faction_loyalty_warning_notices.length > 0) {
            return this.props.faction_loyalty_warning_notices;
        }

        const factionLoyaltyNpc = factionLoyalty.faction_loyalty_npcs.find(
            (factionLoyaltyNpc: FactionLoyaltyNpc) => {
                if (
                    typeof factionLoyaltyNpc.faction_loyalty_warning_notices !==
                        "undefined" &&
                    factionLoyaltyNpc.faction_loyalty_warning_notices !== null
                ) {
                    return (
                        factionLoyaltyNpc.faction_loyalty_warning_notices
                            .length > 0
                    );
                }

                return (
                    factionLoyaltyNpc.faction_loyalty_warning_notice !== null &&
                    typeof factionLoyaltyNpc.faction_loyalty_warning_notice !==
                        "undefined"
                );
            },
        );

        if (typeof factionLoyaltyNpc === "undefined") {
            return [];
        }

        if (
            typeof factionLoyaltyNpc.faction_loyalty_warning_notices !==
                "undefined" &&
            factionLoyaltyNpc.faction_loyalty_warning_notices !== null
        ) {
            return factionLoyaltyNpc.faction_loyalty_warning_notices;
        }

        if (
            factionLoyaltyNpc.faction_loyalty_warning_notice !== null &&
            typeof factionLoyaltyNpc.faction_loyalty_warning_notice !==
                "undefined"
        ) {
            return [factionLoyaltyNpc.faction_loyalty_warning_notice];
        }

        return [];
    }

    buildNpcList(handler: (npc: FactionLoyaltyNpcListItem) => void) {
        return this.state.npcs.map((npc: FactionLoyaltyNpcListItem) => {
            return {
                name: npc.name,
                icon_class: "ra ra-aura",
                on_click: () => handler(npc),
            };
        });
    }

    selectedNpc(): string | undefined {
        return this.state.npcs?.find((npc: FactionLoyaltyNpcListItem) => {
            return npc.name === this.state.selected_npc?.name;
        })?.name;
    }

    switchToNpc(npc: FactionLoyaltyNpcListItem) {
        if (this.isAnyFactionLoyaltyActionBlocked()) {
            return;
        }

        if (!this.state.faction_loyalty) {
            return;
        }

        const selectedFactionLoyaltyNpc =
            this.state.faction_loyalty.faction_loyalty_npcs.filter(
                (factionLoyaltyNpc: FactionLoyaltyNpc) => {
                    return factionLoyaltyNpc.npc_id === npc.id;
                },
            )[0];

        this.setState({
            selected_npc: npc,
            selected_faction_loyalty_npc: selectedFactionLoyaltyNpc,
        });
        this.props.update_faction_loyalty_warning(
            this.hasWarningNotice(this.state.faction_loyalty),
            this.getWarningNotices(this.state.faction_loyalty),
        );
    }

    isAssisting(): boolean {
        if (!this.state.selected_faction_loyalty_npc) {
            return false;
        }

        return this.state.selected_faction_loyalty_npc.currently_helping;
    }

    normalizeAttackType(attackType: string | null): string {
        if (attackType === null) {
            return "attack";
        }

        return attackType.toLowerCase().split(" ").join("_");
    }

    setAttackType(attackType: string): void {
        this.setState({
            attack_type: attackType,
        });
    }

    showAutomationScreen(): void {
        this.setState({
            show_automation_screen: true,
            success_message: null,
            error_message: null,
        });
    }

    returnToTasks(successMessage?: string): void {
        this.setState({
            show_automation_screen: false,
            success_message:
                typeof successMessage !== "undefined"
                    ? successMessage
                    : this.state.success_message,
        });
    }

    updateAutomationRunning(isRunning: boolean): void {
        this.setState({
            is_faction_loyalty_automation_running: isRunning,
            automation_time_out: isRunning ? this.state.automation_time_out : 0,
        });
    }

    updateAutomationTimer(timeLeft: number): void {
        this.setState({
            automation_time_out: timeLeft,
        });
    }

    stopAutomation(): void {
        this.setState(
            {
                is_processing: true,
                success_message: null,
                error_message: null,
            },
            () => {
                new Ajax()
                    .setRoute(
                        "faction-loyalty-automation/" +
                            this.props.character_id +
                            "/stop",
                    )
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState(
                                {
                                    is_processing: false,
                                    success_message:
                                        result.data.message ??
                                        "Faction Loyalty Automation stopped.",
                                    automation_time_out: 0,
                                    is_faction_loyalty_automation_running:
                                        false,
                                    show_automation_screen: false,
                                },
                                () => {
                                    updateTimers(this.props.character_id);
                                },
                            );
                        },
                        (error: AxiosError) => {
                            this.setState({
                                is_processing: false,
                            });

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

    isFactionLoyaltyAutomationRunning(): boolean {
        return this.state.is_faction_loyalty_automation_running;
    }

    isAnyFactionLoyaltyActionBlocked(): boolean {
        return (
            this.props.is_automation_running ||
            this.props.is_delve_running ||
            this.isFactionLoyaltyAutomationRunning()
        );
    }

    selectedNpcHasIncompleteTasks(): boolean {
        if (!this.state.selected_faction_loyalty_npc) {
            return false;
        }

        return this.state.selected_faction_loyalty_npc.faction_loyalty_npc_tasks.fame_tasks.some(
            (fameTask: FameTasks) => {
                return fameTask.current_amount < fameTask.required_amount;
            },
        );
    }

    getAutomationDisabledReason(): string | null {
        if (this.state.is_processing) {
            return "Please wait for the current request to finish.";
        }

        if (!this.state.selected_faction_loyalty_npc) {
            return "Select an NPC before starting automation.";
        }

        if (!this.state.selected_faction_loyalty_npc.currently_helping) {
            return "Assist this NPC before starting automation.";
        }

        if (
            this.state.selected_faction_loyalty_npc.npc.game_map_id !==
            this.props.character_map_id
        ) {
            return "You must be on the same plane as the NPC you are assisting to start Faction Loyalty Automation.";
        }

        if (!this.selectedNpcHasIncompleteTasks()) {
            return "This NPC has no incomplete tasks to automate.";
        }

        if (
            this.props.is_automation_running &&
            !this.isFactionLoyaltyAutomationRunning()
        ) {
            return "Exploration is running. Pledge/unpledge, allegiance switching, NPC assist, bounties, NPC crafting, and Faction Loyalty automation are unavailable until Exploration is canceled.";
        }

        if (this.props.is_delve_running) {
            return "Delve is running. Pledge/unpledge, allegiance switching, NPC assist, bounties, NPC crafting, and Faction Loyalty automation are unavailable until Delve is canceled.";
        }

        if (this.isFactionLoyaltyAutomationRunning()) {
            return "Faction Loyalty Automation is running. Pledge/unpledge, allegiance switching, NPC assist, bounties, NPC crafting, and Faction Loyalty automation are unavailable until Faction Loyalty Automation is canceled.";
        }

        return null;
    }

    render() {
        if (this.state.is_loading || this.state.faction_loyalty === null) {
            return (
                <div className="w-1/2 m-auto">
                    <LoadingProgressBar />
                </div>
            );
        }

        if (!this.state.selected_faction_loyalty_npc) {
            return (
                <DangerAlert additional_css={"my-4"}>
                    Uh oh. We encountered an error here. Seems there is no
                    Faction Loyalty info for this NPC. Not sure how that
                    happened, but I would tell The Creator to investigate how
                    the Faction Loyalty info is fetched for an NPC.
                </DangerAlert>
            );
        }

        if (this.state.error_message !== null) {
            return (
                <DangerAlert additional_css={"my-4"}>
                    {this.state.error_message}
                </DangerAlert>
            );
        }

        if (this.state.show_automation_screen) {
            return (
                <FactionLoyaltyAutomation
                    character_id={this.props.character_id}
                    attack_type={this.state.attack_type ?? "attack"}
                    return_to_tasks={this.returnToTasks.bind(this)}
                    update_automation_running={this.updateAutomationRunning.bind(
                        this,
                    )}
                />
            );
        }

        const automationDisabledReason = this.getAutomationDisabledReason();
        const factionLoyaltyActionsBlockedMessage =
            this.props.is_automation_running &&
            !this.isFactionLoyaltyAutomationRunning()
                ? "Exploration is running. Pledge/unpledge, allegiance switching, NPC assist, bounties, NPC crafting, and Faction Loyalty automation are unavailable until Exploration is canceled."
                : this.props.is_delve_running
                  ? "Delve is running. Pledge/unpledge, allegiance switching, NPC assist, bounties, NPC crafting, and Faction Loyalty automation are unavailable until Delve is canceled."
                  : this.isFactionLoyaltyAutomationRunning()
                    ? "Faction Loyalty Automation is running. Pledge/unpledge, allegiance switching, NPC assist, bounties, NPC crafting, and Faction Loyalty automation are unavailable until Faction Loyalty Automation is canceled."
                    : null;

        return (
            <div>
                <div className="py-4">
                    <h2>
                        {this.state.game_map_name} Loyalty{" "}
                        {this.hasWarningNotice(this.state.faction_loyalty) ? (
                            <i className="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400"></i>
                        ) : null}
                    </h2>
                    <p className="my-4">
                        Below you can select an NPC to assist. Each NPC will
                        have it's own set of tasks to complete. Crafting tasks
                        can be done any where. Bounties must be completed on the
                        NPC&apos;s plane. Faction Loyalty Automation can handle
                        bounty and crafting tasks while it is running.
                    </p>
                    <p className="my-4">
                        In order to gain fame, you must assist the NPC and by
                        completing their tasks you will level the fame and gain
                        the rewards as indicated but multiplied by the level of
                        the npc's fame. You may only assist one NPC at a time
                        and can freely switch at anytime.
                    </p>
                    <p className="my-4">
                        <a
                            href="/information/faction-loyalty"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="my-2"
                        >
                            Learn more about Faction Loyalties{" "}
                            <i className="fas fa-external-link-alt"></i>
                        </a>
                    </p>
                    <div className="my-4">
                        {this.state.success_message ? (
                            <SuccessAlert>
                                {this.state.success_message}
                            </SuccessAlert>
                        ) : null}
                    </div>
                    <div className="my-4">
                        {this.state.is_processing ? (
                            <LoadingProgressBar />
                        ) : null}
                    </div>
                    {factionLoyaltyActionsBlockedMessage !== null ? (
                        <WarningAlert additional_css={"my-4"}>
                            {factionLoyaltyActionsBlockedMessage}
                        </WarningAlert>
                    ) : null}
                    <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                    <div className="my-4 flex flex-wrap md:flex-nowrap gap-2">
                        <div className="flex-none mt-[-25px] md:w-1/2">
                            <div className="w-full relative left-0 flex flex-wrap">
                                <div>
                                    <DropDown
                                        menu_items={this.buildNpcList(
                                            this.switchToNpc.bind(this),
                                        )}
                                        button_title={"NPCs"}
                                        selected_name={this.selectedNpc()}
                                        disabled={this.isAnyFactionLoyaltyActionBlocked()}
                                    />
                                </div>
                                <div>
                                    {this.isAssisting() ? (
                                        <DangerOutlineButton
                                            button_label={"Stop Assisting"}
                                            on_click={() =>
                                                this.manageAssistingNpc(true)
                                            }
                                            additional_css={"mt-[18px] ml-4"}
                                            disabled={this.isAnyFactionLoyaltyActionBlocked()}
                                        />
                                    ) : (
                                        <PrimaryOutlineButton
                                            button_label={"Assist"}
                                            on_click={() =>
                                                this.manageAssistingNpc(false)
                                            }
                                            additional_css={"mt-[18px] ml-4"}
                                            disabled={this.isAnyFactionLoyaltyActionBlocked()}
                                        />
                                    )}
                                </div>
                                <div>
                                    <div className="mt-[38px] ml-4 font-bold">
                                        <span>{this.selectedNpc()}</span>
                                    </div>
                                </div>
                            </div>

                            <FactionNpcSection
                                character_id={this.props.character_id}
                                faction_loyalty_npc={
                                    this.state.selected_faction_loyalty_npc
                                }
                                can_craft={this.props.can_craft}
                                can_attack={this.props.can_attack}
                                character_map_id={this.props.character_map_id}
                                attack_type={this.state.attack_type}
                            />
                        </div>
                        <div className="flex-none md:flex-auto w-full md:w-1/2">
                            <FactionNpcTasks
                                character_id={this.props.character_id}
                                faction_loyalty_npc={
                                    this.state.selected_faction_loyalty_npc
                                }
                                can_craft={this.props.can_craft}
                                can_attack={this.props.can_attack}
                                character_map_id={this.props.character_map_id}
                                attack_type={this.state.attack_type}
                                set_attack_type={this.setAttackType.bind(this)}
                                automation_disabled_reason={
                                    automationDisabledReason
                                }
                                is_faction_loyalty_automation_running={this.isFactionLoyaltyAutomationRunning()}
                                is_automation_running={
                                    this.props.is_automation_running
                                }
                                is_delve_running={this.props.is_delve_running}
                                automation_time_out={
                                    this.state.automation_time_out
                                }
                                is_automation_processing={
                                    this.state.is_processing
                                }
                                warning_notices={
                                    this.props.faction_loyalty_warning_notices
                                }
                                show_automation_screen={this.showAutomationScreen.bind(
                                    this,
                                )}
                                stop_automation={this.stopAutomation.bind(this)}
                                update_automation_timer={this.updateAutomationTimer.bind(
                                    this,
                                )}
                                update_warning_notices={this.updateWarningNotice.bind(
                                    this,
                                )}
                            />
                        </div>
                    </div>
                </div>
                <ActionsTimers user_id={this.props.user_id} />
            </div>
        );
    }
}
