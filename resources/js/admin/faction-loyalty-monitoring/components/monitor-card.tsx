import React from "react";
import MonitorCardProps from "../types/monitor-card-props";

const cardClasses =
    "rounded-lg border border-gray-200 bg-white p-4 shadow-sm " +
    "dark:border-gray-700 dark:bg-gray-900 sm:p-5";

export default class MonitorCard extends React.Component<MonitorCardProps> {
    render() {
        return <section className={cardClasses}>{this.props.children}</section>;
    }
}
