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
                    Should the status of the queue be "Traveling" the queue will
                    be canceled outright, regardless if its for the building or
                    the entire queue. Should the status be "Requesting",
                    "Building" or "Repairing" we have to send a request to the
                    kingdom to cancel the order for the entire queue or just the
                    building selected. Should the request to cancel get to the
                    kingdom to late, that is there is 1 minute or less remaining
                    on the request, your cancellation request can be rejected.
                    Resource cost will be given back in full. Additional
                    population that was spent or purchased will also be given
                    back how ever your population will never go above its max
                    for that kingdom. (Example: If you have a max of 100
                    population and you purchased 1,000 through the request and
                    then cancel it, your population will go to 100/100, not
                    1,000/100).
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
