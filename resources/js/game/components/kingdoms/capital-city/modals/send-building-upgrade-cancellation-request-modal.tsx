import React from "react";
import DangerAlert from "../../../ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../../ui/progress-bars/loading-progress-bar";
import Dialogue from "../../../ui/dialogue/dialogue";
import { serviceContainer } from "../../../../lib/containers/core-container";
import SuccessAlert from "../../../ui/alerts/simple-alerts/success-alert";
import PrimaryOutlineButton from "../../../ui/buttons/primary-outline-button";
import BuildingCancellationSection from "./partials/building-cancellation-section";
import CancelBuildingRequestAjax from "../../ajax/cancel-building-request-ajax";

export default class SendBuildingUpgradeCancellationRequestModal extends React.Component<
    any,
    any
> {
    private processBuildingCancellationRequest: CancelBuildingRequestAjax;

    constructor(props: any) {
        super(props);

        this.state = {
            loading: false,
            error_message: null,
            success_message: null,
        };

        this.processBuildingCancellationRequest = serviceContainer().fetch(
            CancelBuildingRequestAjax,
        );
    }

    sendRequest(deleteQueue: boolean, buildingId?: number) {
        this.setState(
            {
                loading: true,
                success_message: null,
                error_message: null,
            },
            () => {
                this.processBuildingCancellationRequest.cancelBuildingRequest(
                    this,
                    this.props.character_id,
                    this.props.queue_data.kingdom_id,
                    this.props.queue_datata.queue_id,
                    deleteQueue,
                    buildingId,
                );
            },
        );
    }

    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.manage_modal}
                title={"Send Unit Recruitment Request"}
                primary_button_disabled={this.state.loading}
                secondary_actions={{
                    secondary_button_disabled:
                        this.state.loading ||
                        this.state.success_message !== null,
                    secondary_button_label: "Cancel just this building",
                    handle_action: this.sendRequest.bind(this),
                }}
            >
                <div className="overflow-y-auto max-h-[450px]">
                    <BuildingCancellationSection
                        queue_data={this.props.queue_data}
                    />

                    <div className="my-4 text-center">
                        <PrimaryOutlineButton
                            button_label={
                                "Cancel Entire Queue For This Kingdom"
                            }
                            on_click={this.sendRequest.bind(this)}
                        />
                    </div>

                    {this.state.error_message !== null ? (
                        <DangerAlert additional_css={"my-4"}>
                            {this.state.error_message}
                        </DangerAlert>
                    ) : null}

                    {this.state.success_message !== null ? (
                        <SuccessAlert additional_css={"my-4"}>
                            {this.state.success_message}
                        </SuccessAlert>
                    ) : null}

                    {this.state.loading ? <LoadingProgressBar /> : null}
                </div>
            </Dialogue>
        );
    }
}
