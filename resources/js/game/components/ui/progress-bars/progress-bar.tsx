 import React, {Fragment} from "react";
import ProgressBarProps from "../../../lib/ui/types/progress-bars/progress-bar-props";
import ProgressBarState from "../../../lib/ui/types/progress-bars/progress-bar-state";

export default class ProgressBar extends React.Component<ProgressBarProps, ProgressBarState> {

    private interval: any;

    constructor(props: ProgressBarProps) {
        super(props);

        this.state = {
            time_left: 0,
            percentage_left: 0,
            label: 'seconds',
            time_left_label: 0,
        }

        this.interval = null;
    }

    componentDidMount() {
        this.initializeTimer();
    }

    componentDidUpdate(prevProps: Readonly<ProgressBarProps>, prevState: Readonly<ProgressBarState>, snapshot?: any) {
        if (prevProps.time_remaining != this.props.time_remaining) {
            clearInterval(this.interval);

            this.initializeTimer();
        }

        if (this.state.time_left < 0) {
            clearInterval(this.interval);

            this.setState({
                time_left: 0
            });
        }
    }

    initializeTimer() {
        this.setState({
            time_left: this.props.time_remaining,
            percentage_left: this.props.time_remaining > 0 ? 1.0 : 0.0,
            label: this.getLabel(),
            time_left_label: this.getTimeLabel(this.props.time_remaining),
        }, () => {
            if (this.props.time_remaining > 0 && this.state.time_left > 0) {
                this.interval = setInterval(() => {

                    let newTime = this.state.time_left - 1;

                    if (newTime <= 0) {
                        this.setState({
                            time_left: 0,
                            percentage_left: 0,
                            label: 'seconds',
                            time_left_label: 0,
                        });

                        clearInterval(this.interval);
                    } else {
                        this.setState({
                            time_left: newTime,
                            percentage_left: newTime / this.props.time_remaining,
                            label: this.getLabel(newTime),
                            time_left_label: this.getTimeLabel(newTime),
                        });
                    }
                }, 1000)
            } else {
                clearInterval(this.interval);
            }
        });
    }

    getLabel(newTime?: number): string {
        let label = 'seconds';
        let time  = this.props.time_remaining;

        if (newTime) {
            time = newTime;
        }

        if (time / 3600 >= 1) {
            label = 'hour(s)'
        } else if (time / 60 >= 1) {
            label = 'minute(s)';
        }

        return label;
    }

    getTimeLabel(newTime: number): number {
        let timeLeftLabel = newTime;

        if (newTime / 3600 >= 1) {
            timeLeftLabel = parseInt((newTime / 3600).toFixed(0));
        } else if (newTime / 60 >= 1) {
            timeLeftLabel = parseInt((newTime / 60).toFixed(0));
        }

        return timeLeftLabel;
    }

    render() {
        if ((this.state.percentage_left <= 0 && this.state.time_left <= 0) || this.props.time_remaining === 0) {
            return null;
        }

        return (
            <Fragment>
                <div className="flex justify-between mb-1">
                    <span className="text-base font-medium text-gray-800 dark:text-white">
                        Timeout
                        <i className="ml-2 fas fa-spinner fa-pulse"></i>
                    </span>
                    <span className="text-sm font-medium text-gray-800 dark:text-white">{this.state.time_left_label} {this.state.label} left</span>
                </div>
                <div className="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                    <div className={'h-2.5 rounded-full ' + (
                        this.state.percentage_left >= 0.75 ? 'bg-fuchsia-600 dark:bg-fuchsia-700' :
                        this.state.percentage_left < 0.75 && this.state.percentage_left >= 0.50 ? 'bg-fuchsia-500 dark:bg-fuchsia-600' :
                        this.state.percentage_left >= 0.25 && this.state.percentage_left < 0.50 ? 'bg-fuchsia-400 dark:bg-fuchsia-500' :
                        this.state.percentage_left >= 0.0 && this.state.percentage_left < 0.25 ? 'bg-fuchsia-300 dark:bg-fuchsia-400': ''
                    )} style={{width: (this.state.percentage_left * 100) + '%'}}></div>
                </div>
            </Fragment>
        )
    }
}
