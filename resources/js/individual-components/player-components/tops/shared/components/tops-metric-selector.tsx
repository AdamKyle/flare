import React from "react";
import TopsPeriod from "../types/tops-period";
import TopsMetricSelectorProps from "../types/tops-metric-selector-props";

export default class TopsMetricSelector extends React.Component<TopsMetricSelectorProps> {
    render() {
        if (this.props.metrics.length <= 1) {
            return null;
        }

        return (
            <label className="block">
                <span className="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Metric
                </span>
                <select
                    className="w-full rounded-sm border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    value={this.props.value}
                    onChange={(event) =>
                        this.props.onChange(event.target.value)
                    }
                >
                    {this.props.metrics.map((metric: TopsPeriod) => (
                        <option key={metric.key} value={metric.key}>
                            {metric.label}
                        </option>
                    ))}
                </select>
            </label>
        );
    }
}
