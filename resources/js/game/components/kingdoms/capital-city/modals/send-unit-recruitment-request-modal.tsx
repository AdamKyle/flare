import React from "react";
import DangerAlert from "../../../ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../../ui/progress-bars/loading-progress-bar";
import Dialogue from "../../../ui/dialogue/dialogue";
import ProcessUpgradeBuildingsAjax from "../../ajax/process-upgrade-buildings-ajax";
import { serviceContainer } from "../../../../lib/containers/core-container";
import RepairHelpSection from "./partials/repair-help-section";
import UpgradeHelpSection from "./partials/upgrade-help-section";
import RecruitUnitsSections from "./partials/recruit-units-sections";

export default class SendUnitRecruitmentRequestModal extends React.Component<
    any,
    any
> {
    constructor(props: any) {
        super(props);

        this.state = {
            loading: false,
            error_message: null,
            success_message: null,
        };
    }

    sendRequest() {
        this.setState(
            {
                loading: true,
            },
            () => {
                console.log("Ajax...");
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
                    secondary_button_disabled: this.state.loading,
                    secondary_button_label: "Yes. I understand.",
                    handle_action: this.sendRequest.bind(this),
                }}
            >
                <RecruitUnitsSections />

                {this.state.error_message !== null ? (
                    <DangerAlert additional_css={"my-4"}>
                        {this.state.error_message}
                    </DangerAlert>
                ) : null}

                {this.state.success_message !== null ? (
                    <DangerAlert additional_css={"my-4"}>
                        {this.state.error_message}
                    </DangerAlert>
                ) : null}

                {this.state.loading ? <LoadingProgressBar /> : null}
            </Dialogue>
        );
    }
}
