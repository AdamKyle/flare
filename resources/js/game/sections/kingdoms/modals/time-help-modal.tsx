import React from "react";
import { formatNumber } from "../../../lib/game/format-number";
import HelpDialogue from "../../../components/ui/dialogue/help-dialogue";
import InfoAlert from "../../../components/ui/alerts/simple-alerts/info-alert";

export default class TimeHelpModal extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    buildSeconds(time: number) {
        if (this.props.is_in_seconds) {
            return time;
        }

        return time * 60;
    }

    buildMinutes(time: number) {
        if (this.props.is_in_minutes) {
            return time;
        }

        return time / 60;
    }

    buildHours(time: number) {
        if (this.props.is_in_minutes) {
            return time / 60;
        }

        const minutes = this.buildMinutes(time);

        return minutes / 60;
    }

    buildDays(time: number) {
        let hours = this.buildHours(time);

        return hours / 24;
    }

    render() {
        return (
            <HelpDialogue
                is_open={true}
                manage_modal={this.props.manage_modal}
                title={"Time Break Down"}
                no_scrolling
            >
                <div>
                    <InfoAlert additional_css="my-4">
                        <p>
                            The following will show: How many days{" "}
                            <strong>or</strong> how many hours{" "}
                            <strong>or</strong> how many minutes{" "}
                            <strong>or</strong> how many seconds.
                        </p>
                    </InfoAlert>
                    <dl>
                        <dt>Days</dt>
                        <dd>{formatNumber(this.buildDays(this.props.time))}</dd>
                        <dt>Hours</dt>
                        <dd>
                            {formatNumber(this.buildHours(this.props.time))}
                        </dd>
                        <dt>Minutes</dt>
                        <dd>
                            {formatNumber(this.buildMinutes(this.props.time))}
                        </dd>
                        <dt>Seconds</dt>
                        <dd>
                            {formatNumber(this.buildSeconds(this.props.time))}
                        </dd>
                    </dl>
                </div>
            </HelpDialogue>
        );
    }
}
