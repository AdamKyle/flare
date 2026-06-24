import React from "react";
import { StaleQueue } from "../types/reward-queue";
import RewardQueueCard from "./reward-queue-card";

export default function StaleQueueView({
    staleQueues,
    repairing,
    onBack,
    onRepair,
}: {
    staleQueues: StaleQueue[];
    repairing: boolean;
    onBack: () => void;
    onRepair: () => void;
}) {
    return (
        <div className="space-y-4">
            <div className="flex flex-wrap gap-2">
                <button
                    className="rounded border border-gray-300 px-4 py-2 dark:border-gray-600"
                    onClick={onBack}
                >
                    Back to dashboard
                </button>
                <button
                    className="rounded bg-amber-700 px-4 py-2 text-white disabled:opacity-50"
                    disabled={repairing || staleQueues.length === 0}
                    onClick={onRepair}
                >
                    {repairing ? "Repairing…" : "Repair stale queues"}
                </button>
            </div>
            {staleQueues.map((queue) => (
                <RewardQueueCard
                    key={queue.queue_state_id}
                    title={queue.character_name}
                    description={`Queue state ${queue.queue_state_id}`}
                >
                    <dl className="grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <dt className="font-medium">Started</dt>
                            <dd>{queue.started_at ?? "—"}</dd>
                        </div>
                        <div>
                            <dt className="font-medium">Heartbeat</dt>
                            <dd>{queue.heartbeat_at ?? "Never"}</dd>
                        </div>
                        <div>
                            <dt className="font-medium">Stale age</dt>
                            <dd>
                                {queue.stale_age_seconds === null
                                    ? "Unknown"
                                    : `${queue.stale_age_seconds} seconds`}
                            </dd>
                        </div>
                        <div>
                            <dt className="font-medium">Pending</dt>
                            <dd>{queue.pending_request_count}</dd>
                        </div>
                        <div>
                            <dt className="font-medium">Processing</dt>
                            <dd>{queue.processing_request_count}</dd>
                        </div>
                        <div>
                            <dt className="font-medium">Oldest pending</dt>
                            <dd>
                                {queue.oldest_pending_request_created_at ?? "—"}
                            </dd>
                        </div>
                        <div>
                            <dt className="font-medium">Oldest processing</dt>
                            <dd>
                                {queue.oldest_processing_request_created_at ??
                                    "—"}
                            </dd>
                        </div>
                    </dl>
                    <div className="mt-4 overflow-x-auto">
                        <table className="w-full min-w-[850px] text-left text-sm">
                            <thead>
                                <tr>
                                    <th className="p-2">Status</th>
                                    <th>Priority</th>
                                    <th>Source type</th>
                                    <th>Source ID</th>
                                    <th>Failed reason</th>
                                    <th>Created</th>
                                    <th>Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                {queue.requests.map((request) => (
                                    <tr
                                        className="border-t dark:border-gray-700"
                                        key={request.id}
                                    >
                                        <td className="p-2">
                                            {request.status}
                                        </td>
                                        <td>{request.priority}</td>
                                        <td>{request.source_type}</td>
                                        <td>{request.source_id ?? "—"}</td>
                                        <td>{request.failed_reason ?? "—"}</td>
                                        <td>{request.created_at}</td>
                                        <td>{request.updated_at}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </RewardQueueCard>
            ))}
            {staleQueues.length === 0 && (
                <RewardQueueCard>
                    <p className="text-gray-600 dark:text-gray-300">
                        No stale reward queues were detected.
                    </p>
                </RewardQueueCard>
            )}
        </div>
    );
}
