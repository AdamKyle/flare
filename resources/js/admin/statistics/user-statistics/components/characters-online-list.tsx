import React from "react";
import ComponentLoading from "../../../../game/components/ui/loading/component-loading";
import Ajax from "../../../../game/lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import DropDown from "../../../../game/components/ui/drop-down/drop-down";
import InfoAlert from "../../../../game/components/ui/alerts/simple-alerts/info-alert";
import LoadingProgressBar from "../../../../game/components/ui/progress-bars/loading-progress-bar";

export default class CharactersOnlineList extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            data: [],
            loading: true,
            filter_type: 0,
        };
    }

    componentDidMount() {
        this.fetchCharactersOnline(0);
    }

    fetchCharactersOnline(filter: number) {
        this.setState({
            filter_type: filter,
            loading: !this.state.loading ? true : this.state.loading,
        });

        new Ajax()
            .setRoute("admin/site-statistics/characters-online")
            .setParameters({
                day_filter: filter,
            })
            .doAjaxCall(
                "get",
                (result: AxiosResponse) => {
                    this.setState({
                        data: result.data.characters_online,
                        loading: false,
                    });
                },
                (error: AxiosError) => {
                    console.error(error);
                },
            );
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
                icon_class: "ra ra-bottle-vapors",
                on_click: () => this.fetchCharactersOnline(0),
            },
            {
                name: "Last 7 Days",
                icon_class: "far fa-trash-alt",
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

        if (this.state.data.length === 0) {
            return (
                <p className="p-4 text-center text-red-700 dark:text-red-400">
                    No Characters online.
                </p>
            );
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
                {this.state.data.map((characterOnline: any, index: number) => (
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
                                {this.getTimeLoggedInFor(characterOnline)}
                            </span>
                        </div>

                        {index < this.state.data.length - 1 && (
                            <div className="block my-3 border-b-2 lg:hidden border-b-gray-300 dark:border-b-gray-600"></div>
                        )}
                    </div>
                ))}
            </div>
        );
    }
}
