import React, { Fragment } from "react";
import BasicCard from "../../components/ui/cards/basic-card";
import KingdomDetails from "./kingdom-details";
import Select from "react-select";
import SmallBuildingsSection from "./buildings/small-buildings-section";
import SmallUnitsSection from "./units/small-units-section";
import DangerButton from "../../components/ui/buttons/danger-button";
import KingdomEventListener from "../../lib/game/event-listeners/kingdom-event-listener";
import { serviceContainer } from "../../lib/containers/core-container";
import UpdateKingdomListeners from "../../lib/game/event-listeners/game/update-kingdom-listeners";
import Ajax from "../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import LoadingProgressBar from "../../components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../components/ui/alerts/simple-alerts/danger-alert";
import KingdomProps from "./types/kingdom-props";
import SmallKingdomState from "./types/small-kingdom-state";
import KingdomResourceTransfer from "./kingdom-resource-transfer";
import SmallCouncil from "./capital-city/small-council";

export default class SmallKingdom extends React.Component<
    KingdomProps,
    SmallKingdomState
> {
    private updateKingdomListener: KingdomEventListener;

    constructor(props: KingdomProps) {
        super(props);

        this.state = {
            show_kingdom_details: false,
            which_selected: null,
            kingdom: null,
            loading: true,
            error_message: null,
            show_resource_transfer_panel: false,
            should_reset_resource_transfer: false,
            show_small_council: false,
        };

        this.updateKingdomListener =
            serviceContainer().fetch<KingdomEventListener>(
                UpdateKingdomListeners,
            );

        this.updateKingdomListener.initialize(this, this.props.user_id);

        this.updateKingdomListener.register();
    }

    componentDidMount() {
        new Ajax()
            .setRoute(
                "player-kingdom/" +
                    this.props.kingdom.character_id +
                    "/" +
                    this.props.kingdom.id,
            )
            .doAjaxCall(
                "GET",
                (result: AxiosResponse) => {
                    this.setState({
                        loading: false,
                        kingdom: result.data.kingdom,
                    });
                },
                (error: AxiosError) => {
                    this.setState({ loading: false });

                    if (typeof error.response !== "undefined") {
                        const response: AxiosResponse = error.response;

                        this.setState({
                            error_message: response.data.message,
                        });
                    }
                },
            );

        this.updateKingdomListener.listen();
    }

    manageSmallCouncil() {
        this.setState({
            show_small_council: !this.state.show_small_council,
        });
    }

    manageKingdomDetails() {
        this.setState({
            show_kingdom_details: !this.state.show_kingdom_details,
        });
    }

    showSelected(data: any) {
        this.setState({
            which_selected: data.value,
        });
    }

    closeSelected() {
        this.setState({
            which_selected: null,
        });
    }

    renderSelected() {
        if (this.state.kingdom === null) {
            return;
        }

        switch (this.state.which_selected) {
            case "buildings":
                return (
                    <SmallBuildingsSection
                        kingdom={this.state.kingdom}
                        dark_tables={this.props.dark_tables}
                        close_selected={this.closeSelected.bind(this)}
                        character_gold={this.props.character_gold}
                        view_port={this.props.view_port}
                        user_id={this.props.user_id}
                    />
                );
            case "units":
                return (
                    <SmallUnitsSection
                        kingdom={this.state.kingdom}
                        dark_tables={this.props.dark_tables}
                        close_selected={this.closeSelected.bind(this)}
                        character_gold={this.props.character_gold}
                    />
                );
            default:
                return null;
        }
    }

    showResourceTransferPanel() {
        this.setState({
            show_resource_transfer_panel:
                !this.state.show_resource_transfer_panel,
            should_reset_resource_transfer:
                this.state.show_resource_transfer_panel,
        });
    }

    render() {
        if (this.state.loading || this.state.kingdom === null) {
            return <LoadingProgressBar />;
        }

        if (this.state.error_message !== null) {
            return (
                <BasicCard>
                    <DangerAlert additional_css={"my-4"}>
                        {this.state.error_message}
                    </DangerAlert>
                </BasicCard>
            );
        }

        return (
            <Fragment>
                <BasicCard>
                    {this.state.show_small_council ? (
                        <BasicCard>
                            <div className="text-right cursor-pointer text-red-500">
                                <button
                                    onClick={this.manageSmallCouncil.bind(this)}
                                >
                                    <i className="fas fa-minus-circle"></i>
                                </button>
                            </div>
                            <SmallCouncil
                                kingdom={this.state.kingdom}
                                user_id={this.props.user_id}
                            />
                        </BasicCard>
                    ) : !this.state.show_kingdom_details ? (
                        <div className="grid grid-cols-2">
                            <span>
                                <strong>Kingdom Details</strong>
                            </span>
                            <div className="text-right cursor-pointer text-blue-500">
                                <button
                                    onClick={this.manageKingdomDetails.bind(
                                        this,
                                    )}
                                >
                                    <i className="fas fa-plus-circle"></i>
                                </button>
                            </div>
                        </div>
                    ) : this.state.show_resource_transfer_panel ? (
                        <Fragment>
                            <div className="grid grid-cols-2 mb-5">
                                <span>
                                    <strong>Kingdom Details</strong>
                                </span>
                                <div className="text-right cursor-pointer text-red-500">
                                    <button
                                        onClick={this.showResourceTransferPanel.bind(
                                            this,
                                        )}
                                    >
                                        <i className="fas fa-minus-circle"></i>
                                    </button>
                                </div>
                            </div>

                            <KingdomResourceTransfer
                                kingdom_id={this.props.kingdom.id}
                                character_id={this.props.kingdom.character_id}
                            />
                        </Fragment>
                    ) : (
                        <Fragment>
                            <div className="grid grid-cols-2 mb-5">
                                <span>
                                    <strong>Kingdom Details</strong>
                                </span>
                                <div className="text-right cursor-pointer text-red-500">
                                    <button
                                        onClick={this.manageKingdomDetails.bind(
                                            this,
                                        )}
                                    >
                                        <i className="fas fa-minus-circle"></i>
                                    </button>
                                </div>
                            </div>

                            <KingdomDetails
                                show_small_council={() => true}
                                kingdom={this.state.kingdom}
                                character_gold={this.props.character_gold}
                                close_details={this.props.close_details}
                                show_resource_transfer_card={this.showResourceTransferPanel.bind(
                                    this,
                                )}
                                reset_resource_transfer={
                                    this.state.should_reset_resource_transfer
                                }
                                has_capital_city={this.props.has_capital_city}
                            />
                        </Fragment>
                    )}
                </BasicCard>

                <div className="mt-4">
                    {this.state.which_selected !== null ? (
                        this.renderSelected()
                    ) : (
                        <Fragment>
                            <Select
                                onChange={this.showSelected.bind(this)}
                                options={[
                                    {
                                        label: "Building Management",
                                        value: "buildings",
                                    },
                                    {
                                        label: "Unit Management",
                                        value: "units",
                                    },
                                ]}
                                menuPosition={"absolute"}
                                menuPlacement={"bottom"}
                                styles={{
                                    menuPortal: (base: any) => ({
                                        ...base,
                                        zIndex: 9999,
                                        color: "#000000",
                                    }),
                                }}
                                menuPortalTarget={document.body}
                                value={[
                                    {
                                        label: "Please Select Section",
                                        value: "",
                                    },
                                ]}
                            />
                            <div className="grid gap-3">
                                <DangerButton
                                    button_label={"Close"}
                                    on_click={this.props.close_details}
                                    additional_css={"mt-4"}
                                />
                            </div>
                        </Fragment>
                    )}
                </div>
            </Fragment>
        );
    }
}
