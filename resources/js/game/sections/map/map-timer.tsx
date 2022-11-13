import React, {Fragment} from "react";
import TimerProgressBar from "../../components/ui/progress-bars/timer-progress-bar";
import MapTimerProps from "../../lib/game/map/types/map-timer-props";

export default class MapTimer extends React.Component<MapTimerProps, {}> {

    constructor(props: MapTimerProps) {
        super(props);
    }

    render() {
        if (this.props.automation_time_out !== 0 && this.props.time_left !== 0) {
            return (
                <Fragment>
                    <div className='grid grid-cols-2 gap-2 mb-4'>
                        <div>
                            <TimerProgressBar time_remaining={this.props.time_left} time_out_label={'Movement Timeout'}/>
                        </div>
                        <div>
                            <TimerProgressBar time_remaining={this.props.automation_time_out} time_out_label={'Exploration'}/>
                        </div>
                    </div>
                    <TimerProgressBar time_remaining={this.props.celestial_time_out} time_out_label={'Celestial Timeout'} />
                </Fragment>
            )
        }

        if (this.props.celestial_time_out !== 0 && this.props.time_left !== 0) {
            return (
                <Fragment>
                    <div className='grid grid-cols-2 gap-2 mb-4'>
                        <div>
                            <TimerProgressBar time_remaining={this.props.time_left} time_out_label={'Movement Timeout'}/>
                        </div>
                        <div>
                            <TimerProgressBar time_remaining={this.props.celestial_time_out} time_out_label={'Celestial Timeout'} />
                        </div>
                    </div>
                </Fragment>
            )
        }

        return (
            <Fragment>
                <TimerProgressBar time_remaining={this.props.automation_time_out} time_out_label={'Exploration'}/>
                <TimerProgressBar time_remaining={this.props.time_left} time_out_label={'Movement'}/>
                <TimerProgressBar time_remaining={this.props.celestial_time_out} time_out_label={'Celestial Timeout'} />
            </Fragment>
        );
    }

}
