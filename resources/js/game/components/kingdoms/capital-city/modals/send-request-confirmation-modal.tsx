import React from "react";
import DangerAlert from "../../../ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../../ui/progress-bars/loading-progress-bar";
import Dialogue from "../../../ui/dialogue/dialogue";
import ProcessUpgradeBuildingsAjax from "../../ajax/process-upgrade-buildings-ajax";
import { serviceContainer } from "../../../../lib/containers/core-container";
import RepairHelpSection from "./partials/repair-help-section";
import UpgradeHelpSection from "./partials/upgrade-help-section";
import SuccessMessage from "../../../../sections/game-actions-section/components/gambling-section/success-message";
import SuccessAlert from "../../../ui/alerts/simple-alerts/success-alert";

export default class SendRequestConfirmationModal extends React.Component<
    any,
    any
> {
    private processBuildingRequestsAjax: ProcessUpgradeBuildingsAjax;

    constructor(props: any) {
        super(props);

        this.state = {
            loading: false,
            error_message: null,
            success_message: null,
        };

        this.processBuildingRequestsAjax = serviceContainer().fetch(
            ProcessUpgradeBuildingsAjax,
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
                this.processBuildingRequestsAjax.sendBuildingRequests(
                    this,
                    this.props.character_id,
                    this.props.kingdom_id,
                    this.props.params,
                );
            },
        );
    }

    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.manage_modal}
                title={
                    this.props.repair
                        ? "Send Repair Requests"
                        : "Send Upgrade Requests"
                }
                primary_button_disabled={this.state.loading}
                secondary_actions={{
                    secondary_button_disabled:
                        this.state.loading ||
                        this.state.success_message !== null,
                    secondary_button_label: "Yes. I understand.",
                    handle_action: this.sendRequest.bind(this),
                }}
            >
                {this.props.repair ? (
                    <RepairHelpSection />
                ) : (
                    <UpgradeHelpSection />
                )}

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
            </Dialogue>
        );
    }
}
