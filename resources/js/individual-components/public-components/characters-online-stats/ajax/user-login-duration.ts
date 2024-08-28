import { inject, injectable } from "tsyringe";
import Ajax from "../../../../game/lib/ajax/ajax.js";
import AjaxInterface from "../../../../game/lib/ajax/ajax-interface.js";
import { AxiosError, AxiosResponse } from "axios";
import CharactersOnlineContainer from "../characters-online-container.js";
import LoginDurationChart from "../components/login-duration-chart.js";
import { AllowedFilters } from "../deffinitions/allowed-filter-types.js";

@injectable()
export default class UserLoginDuration {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public fetchLoginDurationData(
        component: LoginDurationChart,
        filter: AllowedFilters,
    ) {
        this.ajax
            .setRoute("user-login-duration")
            .setParameters({
                daysPast: filter,
            })
            .doAjaxCall(
                "get",
                (result: AxiosResponse) => {
                    component.setState({
                        loading: false,
                        chart_data: this.createDataSet(
                            result.data.stats.data,
                            result.data.stats.labels,
                        ),
                    });
                },
                (error: AxiosError) => {
                    component.setState({
                        loading: false,
                    });

                    if (typeof error.response !== "undefined") {
                        const response: AxiosResponse = error.response;

                        component.setState({
                            error_message: response.data.message,
                        });
                    }
                },
            );
    }

    public fetchCharactersOnlineData(
        component: CharactersOnlineContainer,
        filterType: AllowedFilters,
    ) {
        this.ajax
            .setRoute("characters-online")
            .setParameters({
                day_filter: filterType,
            })
            .doAjaxCall(
                "get",
                (result: AxiosResponse) => {
                    component.setState({
                        loading: false,
                        characters_online_data: result.data.characters_online,
                    });
                },
                (error: AxiosError) => {
                    component.setState({
                        loading: false,
                    });

                    if (typeof error.response !== "undefined") {
                        const response: AxiosResponse = error.response;

                        component.setState({
                            error_message: response.data.message,
                        });
                    }
                },
            );
    }

    private createDataSet(
        data: number[] | [],
        labels: string[] | [],
    ): { login_count: number; date: string }[] {
        const chartData: { login_count: number; date: string }[] = [];

        data.forEach((data: number, index: number) => {
            chartData.push({
                login_count: data,
                date: labels[index],
            });
        });

        return chartData;
    }
}
