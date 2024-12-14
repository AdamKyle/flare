import { AxiosError, AxiosResponse } from "axios";
import { isEqual } from "lodash";
import React, { Fragment } from "react";
import DangerAlert from "../ui/alerts/simple-alerts/danger-alert";
import BasicCard from "../ui/cards/basic-card";
import Table from "../ui/data-tables/table";
import LoadingProgressBar from "../ui/progress-bars/loading-progress-bar";
import TabPanel from "../ui/tabs/tab-panel";
import Tabs from "../ui/tabs/tabs";
import Ajax from "../../../admin/lib/ajax/ajax";
import { watchForDarkModeTableChange } from "../../../admin/lib/game/dark-mode-watcher";
import Kingdom from "./kingdom";
import KingdomLogDetailsView from "./kingdom-log-details";
import SmallKingdom from "./small-kingdom";
import { buildKingdomsColumns } from "./table-columns/build-kingdoms-columns";
import { buildLogsColumns } from "./table-columns/build-logs-columns";
import KingdomListProps from "./types/kingdom-list-props";
import KingdomListState from "./types/kingdom-list-state";
import KingdomDetails from "./deffinitions/kingdom-details";
import KingdomLogDetails from "./deffinitions/kingdom-log-details";
import DangerButton from "../ui/buttons/danger-button";

export default class KingdomsList extends React.Component<
    KingdomListProps,
    KingdomListState
> {
    private tabs: { name: string; key: string; has_logs?: boolean }[];

    constructor(props: KingdomListProps) {
        super(props);

        this.tabs = [
            {
                name: "Kingdoms",
                key: "kingdoms",
            },
            {
                name: "Logs",
                key: "kingdom-logs",
                has_logs: false,
            },
        ];

        this.state = {
            loading: true,
            dark_tables: false,
            selected_kingdom: null,
            selected_log: null,
            already_has_capital_city: false,
        };
    }

    componentDidMount() {
        watchForDarkModeTableChange(this);

        const self = this;

        setTimeout(function () {
            self.setState({
                loading: false,
            });
        }, 500);

        this.updateIcon();
    }

    componentDidUpdate() {
        const foundKingdom = this.props.my_kingdoms.filter(
            (kingdom: KingdomDetails) => {
                if (this.state.selected_kingdom === null) {
                    return;
                }

                return kingdom.id === this.state.selected_kingdom.id;
            },
        );

        if (foundKingdom.length > 0) {
            const kingdom: KingdomDetails = foundKingdom[0];

            if (!isEqual(kingdom, this.state.selected_kingdom)) {
                this.setState({
                    selected_kingdom: kingdom,
                });
            }
        }

        this.updateIcon();
    }

    updateIcon() {
        if (this.props.logs.length > 0) {
            const hasUnReadLogs = this.props.logs.filter(
                (log: KingdomLogDetails) => {
                    return !log.opened;
                },
            );

            if (hasUnReadLogs.length > 0) {
                this.tabs[this.tabs.length - 1].has_logs = true;
            } else {
                this.tabs[this.tabs.length - 1].has_logs = false;
            }
        }
    }

    viewKingdomDetails(kingdom: KingdomDetails) {
        this.setState({
            selected_kingdom: kingdom,
            already_has_capital_city:
                this.props.my_kingdoms.filter((myKingdom: any) => {
                    return (
                        myKingdom.id !== kingdom.id &&
                        myKingdom.game_map_name === kingdom.game_map_name &&
                        myKingdom.is_capital
                    );
                }).length > 0 && !kingdom.is_capital,
        });
    }

    viewLogs(log: KingdomLogDetails) {
        if (!log.opened) {
            new Ajax()
                .setRoute(
                    "kingdom/opened-log/" + log.character_id + "/" + log.id,
                )
                .doAjaxCall(
                    "post",
                    (result: AxiosResponse) => {
                        this.setState({
                            selected_log: log,
                        });
                    },
                    (error: AxiosError) => {
                        console.error(error);
                    },
                );
        } else {
            this.setState({
                selected_log: log,
            });
        }
    }

    deleteLog(log: KingdomLogDetails) {
        new Ajax()
            .setRoute("kingdom/delete-log/" + log.character_id + "/" + log.id)
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {},
                (error: AxiosError) => {
                    console.error(error);
                },
            );
    }

    deleteAllLogs() {
        new Ajax()
            .setRoute("kingdom/delete-all-logs/" + this.props.character_id)
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {},
                (error: AxiosError) => {
                    console.error(error);
                },
            );
    }

    closeKingdomDetails() {
        this.setState({
            selected_kingdom: null,
            already_has_capital_city: false,
        });
    }

    closeLogDetails() {
        this.setState({
            selected_log: null,
        });
    }

    render() {
        if (this.state.loading) {
            return <LoadingProgressBar />;
        }

        return (
            <Fragment>
                {this.props.is_dead ? (
                    <DangerAlert additional_css={"my-4"}>
                        Christ child! You are dead. Dead people cannot do a lot
                        of things including: Manage inventory, Manage Skills -
                        including passives, Manage Boons or even use items. And
                        they cannot manage their kingdoms! How sad! Go resurrect
                        child! (head to Game tab and click Revive).
                    </DangerAlert>
                ) : null}
                {this.state.selected_kingdom !== null ? (
                    this.props.view_port < 1600 ? (
                        <SmallKingdom
                            close_details={this.closeKingdomDetails.bind(this)}
                            kingdom={this.state.selected_kingdom}
                            dark_tables={this.state.dark_tables}
                            character_gold={this.props.character_gold}
                            view_port={this.props.view_port}
                            user_id={this.props.user_id}
                            kingdoms={this.props.my_kingdoms}
                            has_capital_city={
                                this.state.already_has_capital_city
                            }
                        />
                    ) : (
                        <Kingdom
                            close_details={this.closeKingdomDetails.bind(this)}
                            kingdom={this.state.selected_kingdom}
                            has_capital_city={
                                this.state.already_has_capital_city
                            }
                            kingdoms={this.props.my_kingdoms}
                            dark_tables={this.state.dark_tables}
                            character_gold={this.props.character_gold}
                            view_port={this.props.view_port}
                            user_id={this.props.user_id}
                        />
                    )
                ) : (
                    <BasicCard additionalClasses={"overflow-x-auto"}>
                        <Tabs
                            tabs={this.tabs}
                            icon_key={
                                this.props.logs.length > 0
                                    ? "has_logs"
                                    : undefined
                            }
                        >
                            <TabPanel key={"kingdoms"}>
                                {this.props.my_kingdoms.length > 0 ? (
                                    <div
                                        className={
                                            "max-w-[390px] md:max-w-full overflow-x-hidden"
                                        }
                                    >
                                        <Table
                                            data={this.props.my_kingdoms}
                                            columns={buildKingdomsColumns(
                                                this.viewKingdomDetails.bind(
                                                    this,
                                                ),
                                            )}
                                            dark_table={this.state.dark_tables}
                                        />
                                    </div>
                                ) : (
                                    <Fragment>
                                        <p className="my-4 text-center">
                                            No Settled Kingdoms.
                                        </p>
                                        <p className="text-center">
                                            <a
                                                href="/information/kingdoms"
                                                target="_blank"
                                            >
                                                What are and how to get
                                                kingdoms.{" "}
                                                <i className="fas fa-external-link-alt"></i>
                                            </a>
                                        </p>
                                    </Fragment>
                                )}
                            </TabPanel>
                            <TabPanel key={"kingdom-logs"}>
                                {this.props.logs.length > 0 ? (
                                    <div
                                        className={
                                            "max-w-[390px] md:max-w-full overflow-x-hidden"
                                        }
                                    >
                                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>

                                        {this.state.selected_log !== null ? (
                                            <KingdomLogDetailsView
                                                close_details={this.closeLogDetails.bind(
                                                    this,
                                                )}
                                                log={this.state.selected_log}
                                            />
                                        ) : (
                                            <>
                                                <DangerButton
                                                    button_label={
                                                        "Delete All Logs"
                                                    }
                                                    on_click={this.deleteAllLogs.bind(
                                                        this,
                                                    )}
                                                />
                                                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                                                <Table
                                                    data={this.props.logs}
                                                    columns={buildLogsColumns(
                                                        this.viewLogs.bind(
                                                            this,
                                                        ),
                                                        this.deleteLog.bind(
                                                            this,
                                                        ),
                                                    )}
                                                    dark_table={
                                                        this.state.dark_tables
                                                    }
                                                />
                                            </>
                                        )}
                                    </div>
                                ) : (
                                    <p className="my-4 text-center">No Logs.</p>
                                )}
                            </TabPanel>
                        </Tabs>
                    </BasicCard>
                )}
            </Fragment>
        );
    }
}
