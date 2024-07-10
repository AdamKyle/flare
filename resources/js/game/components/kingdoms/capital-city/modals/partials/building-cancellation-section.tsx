import React from "react";
import WarningAlert from "../../../../ui/alerts/simple-alerts/warning-alert";
import { capitalize } from "lodash";

export default class BuildingCancellationSection extends React.Component<
    any,
    any
> {
    constructor(props: any) {
        super(props);
    }

    renderStatus() {
        if (this.props.queue_data.status === "traveling") {
            return "Traveling";
        }

        return capitalize(this.props.queue_data.secondary_status);
    }

    render() {
        return (
            <div>
                <p className="my-2">
                    Are you sure you want to do this? Below you will find some
                    data about your current request status. You are currently
                    asking to cancel: {this.props.queue_data.building_name}{" "}
                    request. However, you may request all orders of this queue
                    be canceled. If so, click the button below entitled: "Cancel
                    Entire Queue For This Kingdom."
                </p>
                <p className="my-2">
                    Should your request status be with in Building or Requesting
                    - then we will have to send the request to the kingdom,
                    which may result in the request being completed before the
                    request to cancel gets there. Traveling, will just cancel
                    the current request - be it the building selected or the
                    entire request.
                </p>
                <dl>
                    <dt>Building Name</dt>
                    <dd>{this.props.queue_data.building_name}</dd>
                    <dt>For Kingdom</dt>
                    <dd>{this.props.queue_data.kingdom_name}</dd>
                    <dt>Current Status</dt>
                    <dd>{this.renderStatus()}</dd>
                    <dt>Has to travel to deliver request?</dt>
                    <dd>
                        {this.props.queue_data.secondary_status ===
                            "requesting" ||
                        this.props.queue_data.secondary_status === "building"
                            ? "Yes"
                            : "No"}
                    </dd>
                </dl>
            </div>
        );
    }
}
