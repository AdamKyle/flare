import React from "react";
import DropDown from "../../../../game/components/ui/drop-down/drop-down";
import InfoAlert from "../../../../game/components/ui/alerts/simple-alerts/info-alert";
import LoadingProgressBar from "../../../../game/components/ui/progress-bars/loading-progress-bar";
import UserLoginDuration from "../ajax/user-login-duration";
import { charactersOnlineContainer } from "../container/characters-online-container";
import DangerAlert from "../../../../game/components/ui/alerts/simple-alerts/danger-alert";
import { AllowedFilters } from "../deffinitions/allowed-filter-types";
import { CharactersOnlineListProps } from "../types/character-online-list-props";
import { CharactersOnlineListState } from "../types/character-online-list-state";

export default class CharactersOnlineList extends React.Component<
    CharactersOnlineListProps,
    CharactersOnlineListState
> {
    private userLoginAjax: UserLoginDuration;

    constructor(props: CharactersOnlineListProps) {
        super(props);

        this.state = {
            characters_online_data: props.initialCharactersOnline ?? [],
            loading: props.initialLoading ?? true,
            filter_type: 0,
            error_message: "",
        };

        this.userLoginAjax =
            charactersOnlineContainer().fetch(UserLoginDuration);
    }

    componentDidMount() {
        if (!this.props.initialCharactersOnline) {
            this.userLoginAjax.fetchCharactersOnlineData(this, 0);
        }

        this.subscribeToUpdates();
    }

    componentWillUnmount() {
        const echo = (window as any).Echo;

        if (echo) {
            echo.leave("whos-playing-statistics");
        }
    }

    componentDidUpdate(previousProps: CharactersOnlineListProps) {
        if (
            previousProps.initialCharactersOnline !==
                this.props.initialCharactersOnline &&
            this.state.filter_type === 0
        ) {
            this.setState({
                characters_online_data:
                    this.props.initialCharactersOnline ?? [],
                loading: this.props.initialLoading ?? false,
            });
        }
    }

    subscribeToUpdates() {
        const echo = (window as any).Echo;

        if (!echo) {
            return;
        }

        echo.channel("whos-playing-statistics").listen(
            ".whos.playing.statistics.updated",
            (payload: { snapshot: { characters_online: any[] } }) => {
                if (payload.snapshot && this.state.filter_type === 0) {
                    this.setState({
                        characters_online_data:
                            payload.snapshot.characters_online,
                        loading: false,
                        error_message: "",
                    });
                }
            },
        );
    }

    fetchCharactersOnline(filter: AllowedFilters) {
        this.userLoginAjax.fetchCharactersOnlineData(this, filter);
    }

    getTimeLoggedInFor(characterOnline: any): string {
        if (characterOnline.duration < 60) {
            return characterOnline.duration + " Seconds";
        }

        if (characterOnline.duration >= 86400) {
            return (characterOnline.duration / 86400).toFixed(0) + " Days";
        }

        if (characterOnline.duration >= 3600) {
            return (characterOnline.duration / 3600).toFixed(0) + " Hours";
        }

        if (characterOnline.duration >= 60) {
            return (characterOnline.duration / 60).toFixed(0) + " Minutes";
        }

        return "unknown";
    }

    renderDate(value: string | null): string {
        if (!value) {
            return "Unknown";
        }

        return new Date(value).toLocaleString();
    }

    renderCurrentlyExploringLink(): JSX.Element {
        return (
            <>
                (Currently{" "}
                <a href="/information/exploration" target="_blank">
                    exploring <i className="fas fa-external-link-alt"></i>
                </a>
                )
            </>
        );
    }

    dropDownOptions() {
        return [
            {
                name: "Today",
                icon_class: "fas fa-calendar-day",
                on_click: () => this.fetchCharactersOnline(0),
            },
            {
                name: "Last 7 Days",
                icon_class: "fas fa-calendar",
                on_click: () => this.fetchCharactersOnline(7),
            },
            {
                name: "Last 14 Days",
                icon_class: "far fa-trash-alt",
                on_click: () => this.fetchCharactersOnline(14),
            },
            {
                name: "Last Month",
                icon_class: "far fa-trash-alt",
                on_click: () => this.fetchCharactersOnline(31),
            },
        ];
    }

    render() {
        if (this.state.loading) {
            return <LoadingProgressBar />;
        }

        return (
            <div className="flex flex-col gap-2 pt-0 pb-4 pl-4 pr-4">
                <DropDown
                    menu_items={this.dropDownOptions()}
                    button_title="Date Filter"
                />
                {this.state.filter_type > 0 ? (
                    <InfoAlert>
                        Showing character login totals for the selected period.
                    </InfoAlert>
                ) : null}
                {this.state.error_message ? (
                    <DangerAlert additional_css="my-2">
                        {this.state.error_message}
                    </DangerAlert>
                ) : null}
                {this.state.characters_online_data.length > 0 ? (
                    this.state.characters_online_data.map(
                        (characterOnline: any, index: number) => (
                            <div
                                key={`${characterOnline.name}-${index}`}
                                className="rounded-sm border border-gray-200 p-3 dark:border-gray-700"
                            >
                                <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div className="min-w-0">
                                        <div className="flex items-center gap-2">
                                            {this.state.filter_type > 0 ? (
                                                <i className="text-gray-500 fas fa-circle"></i>
                                            ) : (
                                                <i className="text-green-500 fas fa-circle"></i>
                                            )}
                                            <span className="break-words font-bold text-gray-900 dark:text-gray-100">
                                                {characterOnline.name}
                                            </span>
                                        </div>
                                        <div className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                            Level {characterOnline.level} on{" "}
                                            {characterOnline.map ??
                                                "Unknown map"}
                                        </div>
                                        {characterOnline.currently_exploring ? (
                                            <div className="mt-1 text-sm">
                                                {this.renderCurrentlyExploringLink()}
                                            </div>
                                        ) : null}
                                    </div>
                                    <div className="text-sm text-gray-700 dark:text-gray-300 sm:text-right">
                                        <div>
                                            {this.state.filter_type > 0
                                                ? "Duration in period"
                                                : "Online for"}
                                            :{" "}
                                            <strong>
                                                {this.getTimeLoggedInFor(
                                                    characterOnline,
                                                )}
                                            </strong>
                                        </div>
                                        <div>
                                            Last activity:{" "}
                                            {this.renderDate(
                                                characterOnline.last_activity,
                                            )}
                                        </div>
                                        <div>
                                            Last heartbeat:{" "}
                                            {this.renderDate(
                                                characterOnline.last_heart_beat,
                                            )}
                                        </div>
                                    </div>
                                </div>

                                {index <
                                    this.state.characters_online_data.length -
                                        1 && (
                                    <div className="block my-3 border-b-2 lg:hidden border-b-gray-300 dark:border-b-gray-600"></div>
                                )}
                            </div>
                        ),
                    )
                ) : (
                    <p className="p-4 text-center text-red-700 dark:text-red-400">
                        {this.state.filter_type === 0
                            ? "No characters are currently online."
                            : "No character login records were found for this period."}
                    </p>
                )}
            </div>
        );
    }
}
