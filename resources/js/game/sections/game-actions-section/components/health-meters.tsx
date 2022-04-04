import React from "react";
import clsx from "clsx";
import HealthMeterProps from "../../../lib/game/actions/types/health-meter-props";
import { formatNumber } from "../../../lib/game/format-number";

export default class HealthMeters extends React.Component<HealthMeterProps, any> {

    constructor(props: HealthMeterProps) {
        super(props);
    }

    abbreviateNumber(stat: number|undefined): string|number {

        if (typeof stat === 'undefined') {
            return 0;
        }

        let statNumber = stat;

        const symbol = ["", "k", "M", "B", "T", "Quad.", "Qunit."];

        // what tier? (determines SI symbol)
        var tier = Math.log10(Math.abs(statNumber)) / 3 | 0;

        // if zero, we don't need a suffix
        if(tier == 0) return statNumber;

        // get suffix and determine scale
        var suffix = symbol[tier];
        var scale = Math.pow(10, tier * 3);

        // scale the number
        var scaled = statNumber / scale;

        // format number and add suffix
        return scaled.toFixed(1) + suffix;
    }

    render() {
        return(
            <div className='mb-2'>
                <div className="flex justify-between mb-1">
                    <span className={clsx("font-medium dark:text-white text-xs", {
                        'text-red-600 dark:text-red-400': this.props.is_enemy
                    },{
                        'text-green-700 dark:text-green-500': !this.props.is_enemy
                    })}>{this.props.name}</span>
                    <span className={clsx("font-medium dark:text-white text-xs", {
                        'text-red-600 dark:text-red-400': this.props.is_enemy
                    },{
                        'text-green-700 dark:text-green-500': !this.props.is_enemy
                    })}>{this.abbreviateNumber(this.props.current_health)} / {this.abbreviateNumber(this.props.max_health)}</span>
                </div>
                <div className="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                    <div className={clsx("h-1.5 rounded-full", {
                        'bg-red-600': this.props.is_enemy
                    }, {
                        'bg-green-600': !this.props.is_enemy
                    })} style={{width: ((this.props.current_health / this.props.max_health) * 100) + '%'}}></div>
                </div>
            </div>
        )
    }

}
