import React from "react";
import ComponentLoading from "../../../../game/components/ui/loading/component-loading";
import Ajax from "../../../../game/lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";


export default class CharactersOnlineList extends React.Component<
    any,
    any
> {
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

    fetchCharactersOnline(
    ) {
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

    render() {
        if (this.state.loading) {
            return <ComponentLoading />;
        }

        if (this.state.data.length === 0) {
            return (
                <p className="text-center p-4 text-red-700 dark:text-red-400">
                    No Characters online.
                </p>
            );
        }

        return (
            <div className="flex flex-col gap-2 p-4">
                {this.state.data.map((characterOnline: any, index: number) => (
                    <div key={index}>
                        <div className="flex items-center space-x-2">
                            <i className="fas fa-circle text-green-500"></i>
                            <span className="font-bold">{characterOnline.name}</span>
                            <span>Logged in for {characterOnline.duration}h</span>
                        </div>

                        {index < this.state.data.length - 1 && (
                            <div
                                className="border-b-2 block lg:hidden border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                        )}
                    </div>
                ))}
            </div>
        );
    }
}
