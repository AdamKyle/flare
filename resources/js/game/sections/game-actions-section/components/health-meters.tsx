import React from "react";
import clsx from "clsx";
import HealthMeterProps from "../../../lib/game/actions/types/health-meter-props";
import { formatNumber } from "../../../lib/game/format-number";

export default class HealthMeters extends React.Component<HealthMeterProps, any> {

    constructor(props: HealthMeterProps) {
        super(props);
    }

    render() {
        return(
            <div className='mb-2'>
                <div className="flex justify-between mb-1">
                    <span className={clsx("font-medium dark:text-white text-xs", {
                        'text-red-600': this.props.is_enemy
                    },{
                        'text-green-600': !this.props.is_enemy
                    })}>{this.props.name}</span>
                    <span className={clsx("font-medium dark:text-white text-xs", {
                        'text-red-600': this.props.is_enemy
                    },{
                        'text-green-600': !this.props.is_enemy
                    })}>{formatNumber(this.props.current_health)} / {formatNumber(this.props.max_health)}</span>
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
