import React from "react";
import LogSidePeekProps from "../types/log-side-peek-props";
import SeverityBadge from "./severity-badge";

const codeBlockClasses =
    "rounded-md border border-gray-200 bg-gray-50 p-3 text-xs font-mono " +
    "text-gray-800 whitespace-pre-wrap break-words overflow-x-auto " +
    "dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200";

const labelClasses =
    "text-xs font-semibold uppercase tracking-wide text-gray-500 " +
    "dark:text-gray-400";

const valueClasses = "text-sm text-gray-900 break-words dark:text-gray-100";

export default class LogSidePeek extends React.Component<LogSidePeekProps> {
    formatJson(value: unknown): string {
        if (value === null || value === undefined || value === "") {
            return "—";
        }

        if (typeof value === "string") {
            try {
                return JSON.stringify(JSON.parse(value), null, 2);
            } catch {
                return value;
            }
        }

        return JSON.stringify(value, null, 2);
    }

    render() {
        const { entry } = this.props;

        return (
            <aside className="fixed inset-y-0 right-0 z-50 w-full max-w-2xl overflow-y-auto border-l border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900">
                <div className="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-900">
                    <div className="min-w-0">
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Log Detail
                        </h3>
                        <p className="break-all text-sm text-gray-500 dark:text-gray-400">
                            {entry.file_path ?? entry.channel ?? "Log entry"}
                        </p>
                    </div>
                    <button
                        className="shrink-0 rounded border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                        onClick={this.props.onClose}
                    >
                        Close
                    </button>
                </div>

                <div className="space-y-6 px-6 py-5">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1">
                            <p className={labelClasses}>Timestamp</p>
                            <p className={valueClasses}>
                                {entry.timestamp ?? "—"}
                            </p>
                        </div>
                        <div className="space-y-1">
                            <p className={labelClasses}>Level</p>
                            <div>
                                <SeverityBadge severity={entry.severity} />
                            </div>
                        </div>
                        <div className="space-y-1">
                            <p className={labelClasses}>Channel / File</p>
                            <p className={valueClasses}>
                                {entry.channel ?? entry.file_path ?? "—"}
                            </p>
                        </div>
                        {entry.exception_class && (
                            <div className="space-y-1">
                                <p className={labelClasses}>Exception Class</p>
                                <p className={valueClasses}>
                                    {entry.exception_class}
                                </p>
                            </div>
                        )}
                    </div>

                    <div className="space-y-2">
                        <p className={labelClasses}>Message</p>
                        <p className={valueClasses}>{entry.message}</p>
                    </div>

                    {(entry.exception_file || entry.exception_line) && (
                        <div className="space-y-2">
                            <p className={labelClasses}>File / Line</p>
                            <p className={valueClasses}>
                                {entry.exception_file ?? ""}
                                {entry.exception_line
                                    ? `:${entry.exception_line}`
                                    : ""}
                            </p>
                        </div>
                    )}

                    {entry.context && (
                        <div className="space-y-2">
                            <p className={labelClasses}>Context</p>
                            <pre className={codeBlockClasses}>
                                {this.formatJson(entry.context)}
                            </pre>
                        </div>
                    )}

                    {entry.stack_trace && (
                        <div className="space-y-2">
                            <p className={labelClasses}>Stack Trace</p>
                            <pre className={codeBlockClasses}>
                                {entry.stack_trace}
                            </pre>
                        </div>
                    )}

                    {entry.raw_log_entry && (
                        <div className="space-y-2">
                            <p className={labelClasses}>Raw Log Entry</p>
                            <pre className={codeBlockClasses}>
                                {this.formatJson(entry.raw_log_entry)}
                            </pre>
                        </div>
                    )}
                </div>
            </aside>
        );
    }
}
