import React from "react";
import TopsPeriod from "../types/tops-period";
import TopsPeriodSelectorProps from "../types/tops-period-selector-props";

export default class TopsPeriodSelector extends React.Component<TopsPeriodSelectorProps> {
    render() {
        return (
            <label className="block">
                <span className="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Period
                </span>
                <select
                    className="w-full rounded-sm border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    value={this.props.value}
                    onChange={(event) =>
                        this.props.onChange(event.target.value)
                    }
                >
                    {this.props.periods.map((period: TopsPeriod) => (
                        <option key={period.key} value={period.key}>
                            {period.label}
                        </option>
                    ))}
                </select>
            </label>
        );
    }
}
