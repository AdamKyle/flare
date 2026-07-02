import React from "react";
import MonitoringCardProps from "../types/monitoring-card-props";

export default class MonitoringCard extends React.Component<MonitoringCardProps> {
    render() {
        return (
            <section className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:p-5">
                {this.props.title && (
                    <div className="mb-4">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                            {this.props.title}
                        </h2>
                        {this.props.description && (
                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                {this.props.description}
                            </p>
                        )}
                    </div>
                )}
                {this.props.children}
            </section>
        );
    }
}
