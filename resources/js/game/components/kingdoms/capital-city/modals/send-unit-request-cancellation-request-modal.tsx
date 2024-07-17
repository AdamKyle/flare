import React from "react";
import DangerAlert from "../../../ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../../ui/progress-bars/loading-progress-bar";
import Dialogue from "../../../ui/dialogue/dialogue";
import { serviceContainer } from "../../../../lib/containers/core-container";
import SuccessAlert from "../../../ui/alerts/simple-alerts/success-alert";
import PrimaryOutlineButton from "../../../ui/buttons/primary-outline-button";
import UnitCancellationSection from "./partials/unit-cancellation-section";
import CancelUnitRequestAjax from "../../ajax/cancel-unit-request-ajax";

export default class SendUnitRequestCancellationRequestModal extends React.Component<
    any,
    any
> {
    private processBuildingCancellationRequest: CancelUnitRequestAjax;

    constructor(props: any) {
        super(props);

        this.state = {
            loading: false,
            error_message: null,
            success_message: null,
        };

        this.processBuildingCancellationRequest = serviceContainer().fetch(
            CancelUnitRequestAjax,
        );
    }

    sendRequest(deleteQueue: boolean, unitId?: number) {
        this.setState(
            {
                loading: true,
                success_message: null,
                error_message: null,
            },
            () => {
                this.processBuildingCancellationRequest.cancelUnitRequest(
                    this,
                    this.props.character_id,
                    this.props.queue_data.kingdom_id,
                    this.props.queue_data.queue_id,
                    deleteQueue,
                    unitId,
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
                    secondary_button_label: "Cancel just this unit",
                    handle_action: () =>
                        this.sendRequest(false, this.props.queue_data.unit_id),
                }}
            >
                <div className="overflow-y-auto max-h-[450px]">
                    <UnitCancellationSection
                        queue_data={this.props.queue_data}
                    />

                    <div className="my-4 text-center">
                        <PrimaryOutlineButton
                            button_label={
                                "Cancel Entire Queue For This Kingdom"
                            }
                            on_click={() => this.sendRequest(true)}
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
