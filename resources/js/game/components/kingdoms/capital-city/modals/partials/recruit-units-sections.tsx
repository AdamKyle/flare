import React from "react";
import WarningAlert from "../../../../ui/alerts/simple-alerts/warning-alert";

export default class RecruitUnitsSections extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <div className="overflow-y-auto max-h-[450px]">
                <p className="my-2">
                    You are about to send off the request orders for the units
                    below. If you are sure about the amount click "Yes. I
                    understand." You can always go back and make adjustments
                    before sending the orders off.
                </p>

                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>

                <p className="my-2">
                    Each request will be grouped together such that each kingdom
                    processes all their unit requests.
                </p>

                <p className="my-2">
                    As you recruit units, your Kingdom list will change to
                    filter out those who have units in queue or have orders on
                    the way to recruit units.
                </p>

                <p className="my-2">
                    When you recruit units this way, you will eventually get
                    logs for each kingdom to tell you what they recruited, or
                    failed to and how much of each unit was recruited for that
                    kingdom.
                </p>

                <p className="my-2">
                    <strong>If you do not have</strong> enough resources ,{" "}
                    <a href="/information/resource-request" target="_blank">
                        the kingdom will request them{" "}
                        <i className="fas fa-external-link-alt"></i>
                    </a>
                    .
                </p>

                <p className="my-2">
                    If you do not have enough population, the kingdom will buy -{" "}
                    <strong>out of it's treasury</strong>, population needed,
                    assuming you have enough treasury.
                </p>

                <p className="my-2 text-red-700 dark:text-red-500">
                    If you do not have the building leveled, or unlocked for the
                    specific unit, we will not recruit it.
                </p>

                <p className="my-2">
                    A tab will appear when you when you click "Yes. I
                    understand." Called "Recruitment Queue" here you will see
                    the various stages your orders are in and below are some of
                    the statuses.
                </p>

                <p className="my-2">
                    <strong>Traveling</strong>, means the request is headed to
                    the kingdom. If you queued multiple units for the same
                    kingdom, each request is bundled as one request
                </p>

                <p className="my-2">
                    <strong>Processing</strong>, means we are processing your
                    request to see what we need and if we have the resources or
                    not. If you queued multiple units, we process each unit as a
                    separate request.
                </p>

                <p className="my-2">
                    <strong>Requesting Resources</strong>, means the kingdom has
                    sent out a request for resources for that specific unit and
                    thus the unit recruitment is on hold.
                </p>

                <p className="my-2">
                    <strong>Recruiting</strong>, means the kingdom is recruiting
                    the units.
                </p>

                <WarningAlert>
                    The only time you can cancel this process is: Traveling,
                    Requesting Resources or Recruiting, you will see these
                    options when you viewing the Unit Recruitment Queue tab.
                </WarningAlert>
            </div>
        );
    }
}
