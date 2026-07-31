import React from "react";
import BugSidePeekProps from "../types/bug-side-peek-props";
import DetailBlock from "./detail-block";
import SeverityBadge from "./severity-badge";

export default class BugSidePeek extends React.Component<BugSidePeekProps> {
    render() {
        const { bug } = this.props;

        return (
            <aside className="fixed inset-y-0 right-0 z-40 w-full max-w-xl overflow-y-auto border-l border-gray-200 bg-white p-5 shadow-xl dark:border-gray-700 dark:bg-gray-900">
                <div className="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h3 className="text-lg font-semibold">{bug.title}</h3>
                        <p className="text-sm text-gray-500">
                            {bug.occurrence_count} occurrence
                            {bug.occurrence_count === 1 ? "" : "s"}
                        </p>
                    </div>
                    <button
                        className="rounded border border-gray-300 px-3 py-1 text-sm dark:border-gray-600"
                        onClick={this.props.onClose}
                    >
                        Close
                    </button>
                </div>
                <dl className="space-y-4">
                    <DetailBlock label="Fingerprint" value={bug.fingerprint} />
                    <DetailBlock label="Status" value={bug.status} />
                    <DetailBlock label="Severity" value={bug.severity} />
                    <DetailBlock label="First Seen" value={bug.first_seen_at} />
                    <DetailBlock label="Last Seen" value={bug.last_seen_at} />
                    <DetailBlock
                        label="Latest Message"
                        value={bug.latest_message}
                    />
                    <DetailBlock
                        label="Latest Stack Trace"
                        value={bug.latest_stack_trace}
                        pre
                    />
                    <DetailBlock
                        label="Latest Raw Log Entry"
                        value={bug.latest_raw_log_entry}
                        pre
                    />
                </dl>
                <h4 className="mt-5 text-sm font-semibold">
                    Occurrence History
                </h4>
                <div className="mt-2 space-y-2">
                    {bug.occurrences.map((occurrence, index) => (
                        <div
                            key={`${occurrence.occurred_at ?? "unknown"}-${index}`}
                            className="rounded border border-gray-200 p-3 text-sm dark:border-gray-700"
                        >
                            <div className="flex flex-wrap items-center gap-2">
                                <span>
                                    {occurrence.occurred_at ?? "Unknown time"}
                                </span>
                                {occurrence.level && (
                                    <SeverityBadge
                                        severity={occurrence.level}
                                    />
                                )}
                            </div>
                            <p className="mt-1 text-gray-700 dark:text-gray-300">
                                {occurrence.message}
                            </p>
                        </div>
                    ))}
                    {bug.occurrences.length === 0 && (
                        <p className="text-sm text-gray-500">
                            No occurrences loaded.
                        </p>
                    )}
                </div>
            </aside>
        );
    }
}
