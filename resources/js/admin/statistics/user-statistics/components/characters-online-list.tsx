import React from "react";
import ComponentLoading from "../../../../game/components/ui/loading/component-loading";
import Ajax from "../../../../game/lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";

export default class CharactersOnlineList extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            data: [],
            loading: true,
        };
    }

    componentDidMount() {
        this.fetchCharactersOnline();
    }

    fetchCharactersOnline() {
        new Ajax()
            .setRoute("admin/site-statistics/characters-online")
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

    render() {
        if (this.state.loading) {
            return <ComponentLoading />;
        }

        if (this.state.data.length === 0) {
            return (
                <p className="p-4 text-center text-red-700 dark:text-red-400">
                    No Characters online.
                </p>
            );
        }

        return (
            <div className="flex flex-col gap-2 p-4">
                {this.state.data.map((characterOnline: any, index: number) => (
                    <div key={index}>
                        <div className="flex items-center space-x-2">
                            <i className="text-green-500 fas fa-circle"></i>
                            <span className="font-bold">
                                {characterOnline.name}
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
