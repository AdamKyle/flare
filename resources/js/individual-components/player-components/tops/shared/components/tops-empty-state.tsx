import React from "react";
import TopsEmptyStateProps from "../types/tops-empty-state-props";

export default class TopsEmptyState extends React.Component<TopsEmptyStateProps> {
    render() {
        return (
            <div className="rounded-sm border border-gray-200 bg-gray-50 p-6 text-center text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                <p>{this.props.message}</p>
            </div>
        );
    }
}
