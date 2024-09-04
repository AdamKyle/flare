import { AxiosError, AxiosResponse } from "axios";
import Ajax from "../../../../game/lib/ajax/ajax";
import LoginStatistics from "../components/login-statistics";
import RegistrationStatistics from "../components/registration-statistics";

export default class SiteStatisticsAjax {
    private component: LoginStatistics | RegistrationStatistics;

    constructor(component: LoginStatistics | RegistrationStatistics) {
        this.component = component;
    }

    fetchStatisticalData(routeName: string, daysPast: number) {
        new Ajax()
            .setRoute("admin/site-statistics/" + routeName)
            .setParameters({
                daysPast: daysPast,
            })
            .doAjaxCall(
                "get",
                (result: AxiosResponse) => {
                    this.component.setState({
                        data: this.component.createDataSet(
                            result.data.stats.data,
                            result.data.stats.labels,
                        ),
                        loading: false,
                    });
                },
                (error: AxiosError) => {
                    console.error(error);
                },
            );
    }

    createActionsDropDown(routeName: string) {
        return [
            {
                name: "Today",
                icon_class: "ra ra-bottle-vapors",
                on_click: () => this.fetchStatisticalData(routeName, 0),
            },
            {
                name: "Last 7 Days",
                icon_class: "far fa-trash-alt",
                on_click: () => this.fetchStatisticalData(routeName, 7),
            },
            {
                name: "Last 14 Days",
                icon_class: "far fa-trash-alt",
                on_click: () => this.fetchStatisticalData(routeName, 14),
            },
            {
                name: "Last Month",
                icon_class: "far fa-trash-alt",
                on_click: () => this.fetchStatisticalData(routeName, 31),
            },
        ];
    }
}
