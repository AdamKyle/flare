import React from "react";
import RewardQueueCard from "./reward-queue-card";

export default function StaleQueueAlert({
    count,
    repairing,
    onView,
    onRepair,
}: {
    count: number;
    repairing: boolean;
    onView: () => void;
    onRepair: () => void;
}) {
    return (
        <RewardQueueCard className="border-amber-400 bg-amber-50 dark:border-amber-600 dark:bg-amber-950">
            <div role="alert">
                <h2 className="text-lg font-semibold text-amber-900 dark:text-amber-100">
                    {count} stale reward queue{" "}
                    {count === 1 ? "entry" : "entries"} detected
                </h2>
                <p className="mt-2 text-sm text-amber-900 dark:text-amber-100">
                    Processing appears to have paused before completion.
                    Ledger-backed rows can be resumed from their durable step;
                    only legacy pre-ledger rows are failed.
                </p>
                <div className="mt-4 flex flex-wrap gap-2">
                    <button
                        className="rounded border border-amber-700 px-4 py-2 text-amber-900 dark:text-amber-100"
                        onClick={onView}
                    >
                        View stale queues
                    </button>
                    <button
                        className="rounded bg-amber-700 px-4 py-2 text-white disabled:opacity-50"
                        disabled={repairing}
                        onClick={onRepair}
                    >
                        {repairing ? "Recovering…" : "Recover stale queues"}
                    </button>
                </div>
            </div>
        </RewardQueueCard>
    );
}
