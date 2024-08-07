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
                    You are about to send unit request orders. Click "Yes, I
                    understand" if the amount is correct. You can adjust the
                    orders before sending them. If population is insufficient,
                    the kingdom will buy it from the treasury.
                </p>
                <p className="my-2">
                    Logs will show what each kingdom recruited or failed to
                    recruit and the quantities. If resources are insufficient,
                    <a href="/information/resource-request" target="_blank">
                        the kingdom will request them{" "}
                        <i className="fas fa-external-link-alt"></i>
                    </a>
                    .
                </p>
                <p className="my-2">
                    If the necessary building is not leveled or unlocked,
                    recruitment will not occur.
                </p>
                <p className="my-2">
                    <strong>Click View Queue (Orange Button)</strong> to see the
                    orders en route and their various statuses.
                </p>
                <p className="my-2">
                    You can only cancel a unit recruitment when it is in
                    traveling or recruiting phase.
                </p>
            </div>
        );
    }
}
