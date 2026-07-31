import React from "react";
import { DelveLogEntry } from "../types/delve-monitoring";
import RunLogDetailsProps from "../types/run-log-details-props";
import RunLogDetailsState from "../types/run-log-details-state";

const logButtonClasses = "text-xs text-blue-500 underline";

const pageButtonClasses =
    "rounded border border-gray-300 px-2 py-1 disabled:opacity-40 " +
    "dark:border-gray-600";

export default class RunLogDetails extends React.Component<
    RunLogDetailsProps,
    RunLogDetailsState
> {
    public constructor(props: RunLogDetailsProps) {
        super(props);

        this.state = {
            open: false,
            page: 1,
        };
    }

    totalPages() {
        return Math.max(1, Math.ceil(this.props.logs.length / 10));
    }

    rows() {
        return this.props.logs.slice(
            (this.state.page - 1) * 10,
            this.state.page * 10,
        );
    }

    toggleOpen() {
        this.setState({
            open: !this.state.open,
        });
    }

    changePage(page: number) {
        this.setState({
            page,
        });
    }

    renderRows() {
        return this.rows().map((log: DelveLogEntry) => (
            <tr key={log.id} className="border-t dark:border-gray-700">
                <td className="p-1">{log.outcome}</td>
                <td className="p-1">{log.pack_size}</td>
                <td className="p-1">
                    {log.increased_enemy_strength !== null
                        ? `${Math.round((log.increased_enemy_strength ?? 0) * 100)}%`
                        : "—"}
                </td>
            </tr>
        ));
    }

    renderPagination() {
        if (this.totalPages() <= 1) {
            return null;
        }

        return (
            <div className="flex flex-wrap items-center justify-between gap-2 border-t p-2 text-xs dark:border-gray-700">
                <span className="text-gray-600 dark:text-gray-300">
                    Page {this.state.page} of {this.totalPages()}
                </span>
                <div className="flex gap-2">
                    <button
                        className={pageButtonClasses}
                        disabled={this.state.page <= 1}
                        onClick={() => this.changePage(this.state.page - 1)}
                    >
                        Previous
                    </button>
                    <button
                        className={pageButtonClasses}
                        disabled={this.state.page >= this.totalPages()}
                        onClick={() => this.changePage(this.state.page + 1)}
                    >
                        Next
                    </button>
                </div>
            </div>
        );
    }

    render() {
        if (this.props.logs.length === 0) {
            return <span className="text-xs text-gray-400">No logs</span>;
        }

        return (
            <span>
                <button
                    className={logButtonClasses}
                    onClick={() => this.toggleOpen()}
                    aria-expanded={this.state.open}
                >
                    {this.state.open
                        ? "Hide logs"
                        : `Show ${this.props.logs.length} log(s)`}
                </button>
                {this.state.open && (
                    <div className="mt-2 overflow-x-auto rounded border border-gray-200 dark:border-gray-600">
                        <table className="w-full text-left text-xs">
                            <thead>
                                <tr className="border-b bg-gray-50 dark:border-gray-600 dark:bg-gray-800">
                                    <th className="p-1">Outcome</th>
                                    <th className="p-1">Pack size</th>
                                    <th className="p-1">Enemy strength</th>
                                </tr>
                            </thead>
                            <tbody>{this.renderRows()}</tbody>
                        </table>
                        {this.renderPagination()}
                    </div>
                )}
            </span>
        );
    }
}
