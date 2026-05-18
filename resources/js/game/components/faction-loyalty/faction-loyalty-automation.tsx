import { AxiosError, AxiosResponse } from "axios";
import React from "react";
import Ajax from "../../lib/ajax/ajax";
import { updateTimers } from "../../lib/ajax/update-timers";
import DangerAlert from "../ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../ui/alerts/simple-alerts/success-alert";
import LoadingProgressBar from "../ui/progress-bars/loading-progress-bar";

interface FactionLoyaltyAutomationProps {
    character_id: number;
    attack_type: string;
    return_to_tasks: (successMessage?: string) => void;
    update_automation_running: (isRunning: boolean) => void;
}

interface FactionLoyaltyAutomationState {
    is_processing: boolean;
    success_message: string | null;
    error_message: string | null;
}

export default class FactionLoyaltyAutomation extends React.Component<
    FactionLoyaltyAutomationProps,
    FactionLoyaltyAutomationState
> {
    constructor(props: FactionLoyaltyAutomationProps) {
        super(props);

        this.state = {
            is_processing: false,
            success_message: null,
            error_message: null,
        };
    }

    startAutomation() {
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
                            "/start",
                    )
                    .setParameters({
                        attack_type: this.props.attack_type,
                    })
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState(
                                {
                                    is_processing: false,
                                    success_message: result.data.message,
                                },
                                () => {
                                    this.props.update_automation_running(true);
                                    updateTimers(this.props.character_id);
                                    this.props.return_to_tasks(
                                        result.data.message,
                                    );
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

    returnToTasks() {
        this.props.return_to_tasks();
    }

    renderAutomationControls() {
        return (
            <div className="my-4 flex flex-col sm:flex-row gap-2">
                <button
                    type="button"
                    className="w-full sm:w-auto hover:bg-green-700 hover:drop-shadow-md dark:text-white hover:text-gray-300 bg-green-600 dark:bg-green-700 text-white dark:hover:bg-green-600 dark:hover:text-white font-semibold py-2 px-4 rounded-sm drop-shadow-sm disabled:bg-green-500 dark:disabled:bg-green-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-green-200 dark:focus-visible:ring-white focus-visible:ring-opacity-75"
                    onClick={this.startAutomation.bind(this)}
                    disabled={this.state.is_processing}
                    aria-busy={this.state.is_processing}
                >
                    Start Automation
                </button>
                <button
                    type="button"
                    className="w-full sm:w-auto py-2 px-3 text-xs border-blue-500 border-2 font-medium text-center text-gray-900 dark:text-gray-200 hover:text-gray-200 dark:hover:text-gray-300 hover:bg-blue-600 rounded-sm focus:ring-4 focus:ring-blue-300 dark:hover:bg-blue-600 dark:focus:ring-blue-800 disabled:bg-blue-600 disabled:bg-opacity-75 dark:disabled:bg-opacity-50 dark:disabled:bg-blue-500 disabled:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-200 dark:focus-visible:ring-white focus-visible:ring-opacity-75"
                    onClick={this.returnToTasks.bind(this)}
                    disabled={this.state.is_processing}
                    aria-busy={this.state.is_processing}
                >
                    Back
                </button>
            </div>
        );
    }

    render() {
        return (
            <section
                className="w-full max-w-2/3 mx-auto"
                aria-labelledby="faction-loyalty-automation-heading"
            >
                <div>
                    <h2 id="faction-loyalty-automation-heading">
                        Faction Loyalty Automation
                    </h2>
                    <div className="my-4 space-y-3 text-sm sm:text-base">
                        <p>
                            Faction Loyalty Automation works on crafting and
                            bounty tasks for the NPC you are assisting.
                        </p>
                        <p>
                            While it is running, you cannot set up Exploration,
                            Delve, or manually craft items. Bounties still
                            require your character to be on the NPC&apos;s
                            plane.
                        </p>
                        <p>
                            Once started, you can stop the automation from the
                            task screen.
                        </p>
                        <a
                            href="/information/faction-loyalty"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-blue-500 underline"
                        >
                            Read more{" "}
                            <i className="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                    <div aria-live="polite">
                        {this.state.success_message ? (
                            <SuccessAlert additional_css={"my-4"}>
                                {this.state.success_message}
                            </SuccessAlert>
                        ) : null}
                        {this.state.error_message ? (
                            <DangerAlert additional_css={"my-4"}>
                                {this.state.error_message}
                            </DangerAlert>
                        ) : null}
                        {this.state.is_processing ? (
                            <div className="my-4">
                                <LoadingProgressBar />
                            </div>
                        ) : null}
                    </div>
                    {this.renderAutomationControls()}
                </div>
            </section>
        );
    }
}
