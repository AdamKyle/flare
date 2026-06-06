import React, { Fragment } from "react";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import { watchForDarkModeTableChange } from "../../../lib/game/dark-mode-watcher";
import InfoAlert from "../../../components/ui/alerts/simple-alerts/info-alert";
import { DateTime } from "luxon";
import Table from "../../../components/ui/data-tables/table";
import InventoryUseDetails from "./modals/inventory-use-details";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import DangerButton from "../../../components/ui/buttons/danger-button";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import TimerProgressBar from "../../../components/ui/progress-bars/timer-progress-bar";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import { Channel } from "laravel-echo";

declare const Echo: {
    private: (channel: string) => Channel;
};

export default class CharacterActiveBoons extends React.Component<any, any> {
    private boonsUpdate?: Channel;

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            boons: [],
            dark_tables: false,
            show_usable_details: false,
            item_to_use: null,
            removing_boon: false,
            filling_boon_id: null,
            error_message: null,
            success_message: null,
        };
    }

    componentDidMount() {
        watchForDarkModeTableChange(this);

        this.fetchActiveBoons();
        this.listenForBoonUpdates();
    }

    componentWillUnmount() {
        if (this.boonsUpdate) {
            this.boonsUpdate.stopListening(
                "Game.Character.CharacterInventory.Events.CharacterBoonsUpdateBroadcastEvent",
            );
        }
    }

    fetchActiveBoons() {
        if (this.props.finished_loading && this.props.character_id !== null) {
            new Ajax()
                .setRoute(
                    "character-sheet/" +
                        this.props.character_id +
                        "/active-boons",
                )
                .doAjaxCall(
                    "get",
                    (result: AxiosResponse) => {
                        this.setState({
                            loading: false,
                            boons: result.data.active_boons,
                        });
                    },
                    (error: AxiosError) => {
                        console.error(error);
                    },
                );
        }
    }

    listenForBoonUpdates() {
        if (
            this.props.user_id === null ||
            typeof this.props.user_id === "undefined"
        ) {
            return;
        }

        this.boonsUpdate = Echo.private("update-boons-" + this.props.user_id);

        this.boonsUpdate.listen(
            "Game.Character.CharacterInventory.Events.CharacterBoonsUpdateBroadcastEvent",
            () => {
                this.fetchActiveBoons();
            },
        );
    }

    manageBoon(row?: any) {
        this.setState({
            show_usable_details: !this.state.show_usable_details,
            item_to_use: typeof row !== "undefined" ? row.boon_applied : null,
        });
    }

    removeBoon(boonId: number) {
        this.setState(
            {
                removing_boon: true,
                success_message: null,
                error_message: null,
            },
            () => {
                new Ajax()
                    .setRoute(
                        "character-sheet/" +
                            this.props.character_id +
                            "/remove-boon/" +
                            boonId,
                    )
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState({
                                removing_boon: false,
                                boons: result.data.boons,
                                success_message: result.data.message,
                            });
                        },
                        (error: AxiosError) => {
                            let message = "UNKNOWN ERROR - CHECK CONSOLE!";

                            if (error.response !== undefined) {
                                const response: AxiosResponse = error.response;

                                message = response.data.message;
                            }

                            this.setState({
                                removing_boon: false,
                                error_message: message,
                            });

                            console.error(error.response);
                        },
                    );
            },
        );
    }

    fillUpBoon(boonId: number) {
        this.setState(
            {
                filling_boon_id: boonId,
                success_message: null,
                error_message: null,
            },
            () => {
                new Ajax()
                    .setRoute(
                        "character-sheet/" +
                            this.props.character_id +
                            "/fill-up-boon/" +
                            boonId,
                    )
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState({
                                filling_boon_id: null,
                                boons: result.data.boons,
                                success_message: result.data.message,
                            });
                        },
                        (error: AxiosError) => {
                            let message = "UNKNOWN ERROR - CHECK CONSOLE!";

                            if (error.response !== undefined) {
                                const response: AxiosResponse = error.response;

                                message = response.data.message;
                            }

                            this.setState({
                                filling_boon_id: null,
                                error_message: message,
                            });

                            console.error(error.response);
                        },
                    );
            },
        );
    }

    buildColumns() {
        return [
            {
                name: <span title="Name">Name</span>,
                selector: (row: { boon_applied: { name: string } }) =>
                    row.boon_applied.name,
                sortable: true,
                grow: 2,
                minWidth: "120px",
                cell: (row: { id: number; boon_applied: { name: string } }) => (
                    <span
                        className="whitespace-normal break-words"
                        key={
                            row.id +
                            "-" +
                            (Math.random() + 1).toString(36).substring(7)
                        }
                    >
                        <button
                            onClick={() => this.manageBoon(row)}
                            className="text-sky-600 dark:text-sky-300"
                        >
                            {row.boon_applied.name}
                        </button>
                    </span>
                ),
            },
            {
                name: <span title="Amount Used">Amount Used</span>,
                selector: (row: { amount_used: number }) => row.amount_used,
                sortable: true,
                minWidth: "90px",
            },
            {
                name: <span title="Completed In">Completed In</span>,
                selector: (row: { complete: string }) => row.complete,
                sortable: true,
                grow: 3,
                minWidth: "160px",
                cell: (row: { id: number; complete: string }) => (
                    <div
                        className="w-full min-w-0 md:min-w-[240px]"
                        key={
                            row.id +
                            "-" +
                            (Math.random() + 1).toString(36).substring(7)
                        }
                    >
                        <TimerProgressBar
                            time_remaining={this.getSecondsRemaining(
                                row.complete,
                            )}
                            time_out_label={""}
                            additional_css={"w-full"}
                        />
                    </div>
                ),
            },
            {
                name: <span title="Amount Left">Amount Left</span>,
                selector: (row: { amount_left: number }) => row.amount_left,
                sortable: true,
                minWidth: "100px",
            },
            {
                name: <span title="Actions">Actions</span>,
                selector: (row: { id: number; boon_applied: { id: number } }) =>
                    row.boon_applied.id,
                sortable: true,
                minWidth: "230px",
                cell: (row: {
                    id: number;
                    amount_left: number;
                    boon_applied: { id: number };
                }) => (
                    <span
                        className="flex flex-wrap gap-2"
                        key={
                            row.boon_applied.id +
                            "-" +
                            (Math.random() + 1).toString(36).substring(7)
                        }
                    >
                        <PrimaryButton
                            button_label={"Fill Up"}
                            on_click={() => this.fillUpBoon(row.id)}
                            disabled={
                                row.amount_left === 0 ||
                                this.state.filling_boon_id === row.id ||
                                this.state.removing_boon
                            }
                        />
                        <DangerButton
                            button_label={"Remove Boon"}
                            on_click={() => this.removeBoon(row.id)}
                            disabled={
                                this.state.filling_boon_id === row.id ||
                                this.state.removing_boon
                            }
                        />
                    </span>
                ),
            },
        ];
    }

    getSecondsRemaining(completedAt: string): number {
        const completed = DateTime.fromISO(completedAt);

        const time = completed
            .diff(DateTime.now(), ["seconds"])
            .toObject().seconds;

        if (typeof time === "undefined") {
            return 0;
        }

        return Math.max(0, Math.floor(time));
    }

    render() {
        if (this.state.loading) {
            return <LoadingProgressBar />;
        }

        return (
            <Fragment>
                <div className="my-5 w-full min-w-0 max-w-full">
                    {this.state.boons.length > 0 ? (
                        <InfoAlert>
                            This tab does not update in real time. You can
                            switch tabs to get the latest data.
                        </InfoAlert>
                    ) : null}
                    {this.state.removing_boon ? <LoadingProgressBar /> : null}
                    {this.state.filling_boon_id !== null ? (
                        <LoadingProgressBar />
                    ) : null}
                    {this.state.success_message !== null ? (
                        <SuccessAlert additional_css={"my-4"}>
                            <p>{this.state.success_message}</p>
                        </SuccessAlert>
                    ) : null}
                    {this.state.error_message !== null ? (
                        <DangerAlert additional_css={"my-4"}>
                            <p>{this.state.error_message}</p>
                        </DangerAlert>
                    ) : null}
                    <p className="my-4 text-center">
                        <a href="/information/alchemy" target="_blank">
                            What are boons and how do I get them?{" "}
                            <i className="fas fa-external-link-alt"></i>
                        </a>
                    </p>
                    {this.state.boons.length > 0 ? (
                        <div className="w-full min-w-0 max-w-full overflow-x-auto">
                            <Table
                                columns={this.buildColumns()}
                                data={this.state.boons}
                                dark_table={this.state.dark_tables}
                            />
                        </div>
                    ) : (
                        <p className="my-4 text-center">No Active Boons.</p>
                    )}
                </div>

                {this.state.show_usable_details &&
                this.state.item_to_use !== null ? (
                    <InventoryUseDetails
                        is_open={this.state.show_usable_details}
                        manage_modal={this.manageBoon.bind(this)}
                        item={this.state.item_to_use}
                    />
                ) : null}
            </Fragment>
        );
    }
}
