import React, { Fragment } from "react";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import { watchForDarkModeTableChange } from "../../../lib/game/dark-mode-watcher";
import InfoAlert from "../../../components/ui/alerts/simple-alerts/info-alert";
import Table from "../../../components/ui/data-tables/table";
import { formatNumber } from "../../../lib/game/format-number";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import PledgeLoyalty from "../../faction-loyalty/modals/pledge-loyalty";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import DangerButton from "../../../components/ui/buttons/danger-button";

export default class CharacterFactions extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            factions: [],
            dark_tables: false,
            pledge_faction: null,
            success_message: null,
            pledging: false,
        };
    }

    componentDidMount() {
        watchForDarkModeTableChange(this);

        if (this.props.character_id !== null && this.props.finished_loading) {
            new Ajax()
                .setRoute(
                    "character-sheet/" + this.props.character_id + "/factions",
                )
                .doAjaxCall(
                    "get",
                    (result: AxiosResponse) => {
                        this.setState({
                            loading: false,
                            factions: result.data.factions,
                        });
                    },
                    (error: AxiosError) => {
                        console.error(error);
                    },
                );
        }
    }

    handlePledge(pledging: boolean) {
        if (this.props.update_pledge_tab) {
            this.setState(
                {
                    pledging: true,
                },
                () => {
                    let factionId = this.props.pledged_faction_id;

                    if (!factionId || this.state.pledge_faction !== null) {
                        factionId = this.state.pledge_faction.id;
                    }

                    new Ajax()
                        .setRoute(
                            "faction-loyalty/" +
                                (pledging ? "pledge" : `remove-pledge`) +
                                "/" +
                                this.props.character_id +
                                "/" +
                                factionId,
                        )
                        .doAjaxCall(
                            "post",
                            (result: AxiosResponse) => {
                                this.closePledge();

                                this.setState(
                                    {
                                        success_message: result.data.message,
                                        pledging: false,
                                    },
                                    () => {
                                        this.props.update_pledge_tab(
                                            pledging,
                                            pledging ? factionId : null,
                                        );

                                        this.props.update_faction_action_tasks(
                                            null,
                                        );
                                    },
                                );
                            },
                            (error: AxiosError) => {
                                this.setState({
                                    pledging: false,
                                });
                                console.error(error);
                            },
                        );
                },
            );
        }
    }

    buildColumns() {
        return [
            {
                name: "Name",
                selector: (row: any) => row.name,
                sortable: true,
                cell: (row: any) => (
                    <span
                        key={
                            row.id +
                            "-" +
                            (Math.random() + 1).toString(36).substring(7)
                        }
                    >
                        {row.map_name}
                    </span>
                ),
            },
            {
                name: "Title",
                selector: (row: any) => row.title,
                sortable: true,
                cell: (row: any) => (
                    <span
                        key={
                            row.id +
                            "-" +
                            (Math.random() + 1).toString(36).substring(7)
                        }
                    >
                        {row.title !== null ? row.title : "N/A"}
                    </span>
                ),
            },
            {
                name: "Level",
                selector: (row: any) => row.current_level,
                sortable: true,
                cell: (row: any) => (
                    <span
                        key={
                            row.id +
                            "-" +
                            (Math.random() + 1).toString(36).substring(7)
                        }
                    >
                        {row.current_level}
                    </span>
                ),
            },
            {
                name: "Points",
                selector: (row: any) => row.points_needed,
                sortable: true,
                cell: (row: any) => (
                    <span
                        key={
                            row.id +
                            "-" +
                            (Math.random() + 1).toString(36).substring(7)
                        }
                    >
                        {formatNumber(row.current_points)} /{" "}
                        {formatNumber(row.points_needed)}
                    </span>
                ),
            },
            {
                name: "Pledge Loyalty",
                selector: (row: any) => row.id,
                sortable: true,
                cell: (row: any) => (
                    <span
                        key={
                            row.id +
                            "-" +
                            (Math.random() + 1).toString(36).substring(7)
                        }
                    >
                        {this.props.is_pledged &&
                        this.props.pledged_faction_id === row.id ? (
                            <DangerButton
                                button_label={"Un-pledge"}
                                on_click={() => this.handlePledge(false)}
                            />
                        ) : (
                            <PrimaryButton
                                button_label={"Pledge Loyalty"}
                                on_click={() => {
                                    this.pledgeLoyalty(row);
                                }}
                                disabled={!row.maxed}
                            />
                        )}
                    </span>
                ),
            },
        ];
    }

    pledgeLoyalty(row: any): void {
        this.setState({
            pledge_faction: row,
        });
    }

    closePledge() {
        this.setState({
            pledge_faction: null,
        });
    }

    render() {
        if (this.state.loading) {
            return <LoadingProgressBar />;
        }

        return (
            <Fragment>
                <div className="my-5">
                    {this.state.factions.length > 0 ? (
                        <InfoAlert additional_css={"mb-4"}>
                            This tab does not update in real time. You can
                            switch tabs to get the latest data. You can learn
                            more about{" "}
                            <a href="/information/factions" target="_blank">
                                Factions{" "}
                                <i className="fas fa-external-link-alt"></i>
                            </a>{" "}
                            in the help docs. Players who reach the max level
                            (5) of a faction can then{" "}
                            <a
                                href="/information/faction-loyalty"
                                target="_blank"
                            >
                                Pledge their loyalty
                            </a>
                            .
                        </InfoAlert>
                    ) : null}

                    {this.state.success_message !== null ? (
                        <SuccessAlert additional_css={"mb-4"}>
                            {this.state.success_message}
                        </SuccessAlert>
                    ) : null}

                    {this.state.pledge_faction === null &&
                    this.state.pledging ? (
                        <div className="mb-4">
                            <LoadingProgressBar />
                        </div>
                    ) : null}

                    <div
                        className={
                            "max-w-[390px] md:max-w-full overflow-x-hidden"
                        }
                    >
                        <Table
                            columns={this.buildColumns()}
                            data={this.state.factions}
                            dark_table={this.state.dark_tables}
                        />
                    </div>
                </div>

                {this.state.pledge_faction !== null ? (
                    <PledgeLoyalty
                        is_open={true}
                        manage_modal={this.closePledge.bind(this)}
                        faction={this.state.pledge_faction}
                        handle_pledge={() => this.handlePledge(true)}
                        pledging={this.state.pledging}
                    />
                ) : null}
            </Fragment>
        );
    }
}
