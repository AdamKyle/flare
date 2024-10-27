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
            characters_online_data: [],
            loading: true,
            filter_type: 0,
            error_message: "",
        };

        this.userLoginAjax =
            charactersOnlineContainer().fetch(UserLoginDuration);
    }

    componentDidMount() {
        this.userLoginAjax.fetchCharactersOnlineData(this, 0);
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
            return (characterOnline.duration / 3600).toFixed(0) + " Seconds";
        }

        if (characterOnline.duration >= 60) {
            return (characterOnline.duration / 60).toFixed(0) + " Minutes";
        }

        return "unknown";
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
                        You are now looking at the past, these are people who
                        have been online and for how long during that period.
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
                            <div key={index}>
                                <div className="flex items-center space-x-2">
                                    {this.state.filter_type > 0 ? (
                                        <i className="text-gray-500 fas fa-circle"></i>
                                    ) : (
                                        <i className="text-green-500 fas fa-circle"></i>
                                    )}

                                    <span className="font-bold">
                                        {characterOnline.name}{" "}
                                        {characterOnline.currently_exploring
                                            ? this.renderCurrentlyExploringLink()
                                            : ""}
                                    </span>
                                    <span>
                                        Logged in for{" "}
                                        {this.getTimeLoggedInFor(
                                            characterOnline,
                                        )}
                                    </span>
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
                        No Characters online.
                    </p>
                )}
                {}
            </div>
        );
    }
}
