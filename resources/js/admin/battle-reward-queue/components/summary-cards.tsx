import React from "react";
import { Summary } from "../types/reward-queue";
import RewardQueueCard from "./reward-queue-card";

const FILTERABLE_STATUSES = new Set([
    "pending",
    "processing",
    "resumable",
    "completed",
    "failed",
]);

export default function SummaryCards({
    summary,
    onFilter,
}: {
    summary: Summary;
    onFilter?: (status: string) => void;
}) {
    return (
        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
            {Object.entries(summary).map(([label, value]) => {
                const filterable =
                    FILTERABLE_STATUSES.has(label) && onFilter != null;
                if (filterable) {
                    return (
                        <button
                            key={label}
                            type="button"
                            className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:p-5 text-left hover:border-blue-400 dark:hover:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-400 transition-colors cursor-pointer"
                            onClick={() => onFilter(label)}
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
            })}
        </div>
    );
}
