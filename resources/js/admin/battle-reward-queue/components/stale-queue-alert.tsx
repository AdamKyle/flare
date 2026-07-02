import React from "react";
import StaleQueueAlertProps from "../types/stale-queue-alert-props";
import RewardQueueCard from "./reward-queue-card";

export default class StaleQueueAlert extends React.Component<StaleQueueAlertProps> {
    render() {
        return (
            <RewardQueueCard className="border-amber-400 bg-amber-50 dark:border-amber-600 dark:bg-amber-950">
                <div role="alert">
                    <h2 className="text-lg font-semibold text-amber-900 dark:text-amber-100">
                        {this.props.count} stale reward queue{" "}
                        {this.props.count === 1 ? "entry" : "entries"} detected
                    </h2>
                    <p className="mt-2 text-sm text-amber-900 dark:text-amber-100">
                        Processing appears to have paused before completion.
                        Ledger-backed rows can be resumed from their durable
                        step; only legacy pre-ledger rows are failed.
                    </p>
                    <div className="mt-4 flex flex-wrap gap-2">
                        <button
                            className="rounded border border-amber-700 px-4 py-2 text-amber-900 dark:text-amber-100"
                            onClick={this.props.onView}
                        >
                            View stale queues
                        </button>
                        <button
                            className="rounded bg-amber-700 px-4 py-2 text-white disabled:opacity-50"
                            disabled={this.props.repairing}
                            onClick={this.props.onRepair}
                        >
                            {this.props.repairing
                                ? "Recovering…"
                                : "Recover stale queues"}
                        </button>
                    </div>
                </div>
            </RewardQueueCard>
        );
    }
}
