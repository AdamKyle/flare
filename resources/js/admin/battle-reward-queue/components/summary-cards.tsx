import React from "react";
import SummaryCardsProps from "../types/summary-cards-props";
import RewardQueueCard from "./reward-queue-card";

const FILTERABLE_STATUSES = new Set([
    "pending",
    "processing",
    "resumable",
    "completed",
    "failed",
]);

export default class SummaryCards extends React.Component<SummaryCardsProps> {
    isFilterable(label: string) {
        return FILTERABLE_STATUSES.has(label) && this.props.onFilter != null;
    }

    renderCard(label: string, value: number) {
        if (this.isFilterable(label)) {
            return (
                <button
                    key={label}
                    type="button"
                    className="cursor-pointer rounded-lg border border-gray-200 bg-white p-4 text-left shadow-sm transition-colors hover:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-400 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-blue-500 sm:p-5"
                    onClick={() => this.props.onFilter?.(label)}
                    aria-label={`Filter by ${label}`}
                >
                    <div className="text-sm capitalize text-gray-600 dark:text-gray-300">
                        {label}
                    </div>
                    <div className="mt-1 text-3xl font-bold text-gray-900 dark:text-white">
                        {value}
                    </div>
                </button>
            );
        }

        return (
            <RewardQueueCard key={label}>
                <div className="text-sm capitalize text-gray-600 dark:text-gray-300">
                    {label}
                </div>
                <div className="mt-1 text-3xl font-bold text-gray-900 dark:text-white">
                    {value}
                </div>
            </RewardQueueCard>
        );
    }

    render() {
        return (
            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                {Object.entries(this.props.summary).map(([label, value]) =>
                    this.renderCard(label, value),
                )}
            </div>
        );
    }
}
