import React from "react";
import LoadingProgressBar from "../../../../../game/components/ui/progress-bars/loading-progress-bar";
import TopsLoadingStateProps from "../types/tops-loading-state-props";

export default class TopsLoadingState extends React.Component<TopsLoadingStateProps> {
    render() {
        return (
            <div
                aria-live="polite"
                className="rounded-sm border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
            >
                <p className="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Loading leaderboard...
                </p>
                <LoadingProgressBar />
            </div>
        );
    }
}
