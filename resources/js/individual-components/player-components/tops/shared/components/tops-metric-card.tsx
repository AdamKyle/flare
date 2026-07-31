import React from "react";
import { formatTopsValue } from "../helpers/tops-format-value";
import TopsMetricCardProps from "../types/tops-metric-card-props";

export default class TopsMetricCard extends React.Component<TopsMetricCardProps> {
    render() {
        return (
            <div className="rounded-sm border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div className="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {this.props.label}
                </div>
                <div className="mt-1 text-lg font-bold text-gray-900 dark:text-gray-100">
                    {formatTopsValue(this.props.value)}
                </div>
            </div>
        );
    }
}
