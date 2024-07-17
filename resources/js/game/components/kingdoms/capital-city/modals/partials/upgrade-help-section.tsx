import React from "react";
import WarningAlert from "../../../../ui/alerts/simple-alerts/warning-alert";

export default class UpgradeHelpSection extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <div>
                <p className="my-2">
                    You are about to send off requests for the buildings you
                    selected to be upgraded. If you are sure about your
                    selection, click: "Yes. I understand."
                </p>

                <p className="my-2">
                    You can go back to select or unselect buildings.
                </p>

                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>

                <p className="my-2">
                    These requests will show up in your building manage section
                    of your small council. When you click the Building
                    Upgrade/Repair you will see a new table that shows you your
                    requests. Below, you can learn about the types and when you
                    can and cannot cancel a request.
                </p>

                <p className="my-2">
                    <strong>Traveling</strong>, means the request is headed to
                    the kingdom. If you queued multiple buildings for the same
                    kingdom, each request is bundled as one request
                </p>

                <p className="my-2">
                    <strong>Processing</strong>, means we are processing your
                    request to see what we need and if we have the resources or
                    not. If you queued multiple buildings, we process each
                    building as a separate request.
                </p>

                <p className="my-2">
                    <strong>Requesting</strong>, means the kingdom has sent out
                    a request for resources for that specific building and thus
                    the building is on hold.
                </p>

                <p className="my-2">
                    <strong>Building</strong>, means the kingdom is upgrading
                    the building.
                </p>

                <p className="my-2">
                    <strong>Repairing</strong>, means the kingdom is repairing
                    the building.
                </p>

                <WarningAlert>
                    The only time you can cancel this process is: Traveling,
                    Building or Repairing. You will see these options when you
                    viewing the building request queue.
                </WarningAlert>
            </div>
        );
    }
}
