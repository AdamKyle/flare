import React from "react";
import Select, { SingleValue } from "react-select";
import TopsPeriod from "../types/tops-period";
import TopsMetricSelectorProps from "../types/tops-metric-selector-props";

export default class TopsMetricSelector extends React.Component<TopsMetricSelectorProps> {
    options() {
        return this.props.metrics.map((metric: TopsPeriod) => ({
            label: metric.label,
            value: metric.key,
        }));
    }

    selectedOption() {
        return (
            this.options().find(
                (option) => option.value === this.props.value,
            ) ?? null
        );
    }

    render() {
        if (this.props.metrics.length <= 1) {
            return null;
        }

        return (
            <label className="block">
                <span className="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Metric
                </span>
                <Select
                    className="text-sm"
                    classNamePrefix="react-select"
                    options={this.options()}
                    value={this.selectedOption()}
                    onChange={(
                        option: SingleValue<{ label: string; value: string }>,
                    ) => this.props.onChange(option?.value ?? this.props.value)}
                    aria-label="Select leaderboard metric"
                    menuPortalTarget={document.body}
                />
            </label>
        );
    }
}
