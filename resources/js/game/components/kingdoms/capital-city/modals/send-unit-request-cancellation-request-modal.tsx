import React from "react";
import DangerAlert from "../../../ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../../ui/progress-bars/loading-progress-bar";
import Dialogue from "../../../ui/dialogue/dialogue";
import { serviceContainer } from "../../../../lib/containers/core-container";
import SuccessAlert from "../../../ui/alerts/simple-alerts/success-alert";
import PrimaryOutlineButton from "../../../ui/buttons/primary-outline-button";
import CancelUnitRequestAjax from "../../ajax/cancel-unit-request-ajax";
import { CancellationType } from "../enums/cancellation-type";
import UnitDetails from "../../deffinitions/unit-details";

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

    sendRequest() {
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
                    this.props.kingdom_id,
                    this.props.queue_id,
                    this.props.unit_details?.unit_name,
                );
            },
        );
    }

    modalDialogueTitle(): string {
        if (this.props.unit_details === null) {
            return "Cancel All Requests";
        }

        return "Cancel " + this.props.unit_details.unit_name;
    }

    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.manage_modal}
                title={this.modalDialogueTitle()}
                primary_button_disabled={this.state.loading}
                secondary_actions={{
                    secondary_button_disabled:
                        this.state.loading ||
                        this.state.success_message !== null,
                    secondary_button_label: "Yes, that is correct",
                    handle_action: () => this.sendRequest(),
                }}
            >
                <div>
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

                    <p className="my-4">
                        <strong>Are you sure you want to this?</strong> This
                        action cannot be undone.
                    </p>

                    <p>
                        {this.props.cancellation_type ===
                        CancellationType.SINGLE_CANCEL
                            ? "You are saying you want to cancel: " +
                              this.props.unit_details.unit_name +
                              " Is that correct?"
                            : "You are saying to cancel all unit requests in this request. Is that correct?"}
                    </p>

                    {this.state.loading ? <LoadingProgressBar /> : null}
                </div>
            </Dialogue>
        );
    }
}
