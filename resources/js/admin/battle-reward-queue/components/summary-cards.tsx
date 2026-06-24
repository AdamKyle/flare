import React from "react";
import { Summary } from "../types/reward-queue";
import RewardQueueCard from "./reward-queue-card";

export default function SummaryCards({ summary }: { summary: Summary }) {
    return (
        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            {Object.entries(summary).map(([label, value]) => (
                <RewardQueueCard key={label}>
                    <div className="text-sm capitalize text-gray-600 dark:text-gray-300">
                        {label}
                    </div>
                    <div className="mt-1 text-3xl font-bold text-gray-900 dark:text-white">
                        {value}
                    </div>
                </RewardQueueCard>
            ))}
        </div>
    );
}
