import React from "react";
import Dialogue from "../../components/ui/dialogue/dialogue";
import TimerProgressBar from "../../components/ui/progress-bars/timer-progress-bar";

export default class GlobalTimeoutModal extends React.Component<{}, {}> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <Dialogue is_open={true} title={"You're in timeout!"}>
                <p className={"my-4"}>
                    Child! You need to slow down. You have been so busy you spun
                    your self into a dizzying fit of madness. Take a moment
                    child and rest.
                </p>

                <p className={"my-4 text-red-600 dark:text-red-400"}>
                    You have been timed out for two minutes. Refresh and your
                    timer restarts. Try and get around this and you'll be
                    banned. Accept your punishment child and slow down.
                </p>

                <TimerProgressBar
                    time_remaining={120}
                    time_out_label={"Timeout time remaining."}
                    update_time_remaining={() => {}}
                />
            </Dialogue>
        );
    }
}
