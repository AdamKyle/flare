import React from "react";
import {
    ExplorationFilters,
    ExplorationLogRow,
} from "../types/exploration-monitoring";
import ExplorationLogsTableProps from "../types/exploration-logs-table-props";
import MonitoringCard from "./monitoring-card";
import PaginationControls from "./pagination-controls";

export default class ExplorationLogsTable extends React.Component<ExplorationLogsTableProps> {
    updateFilter(name: keyof ExplorationFilters, value: string | boolean) {
        this.props.onFiltersChange({
            ...this.props.filters,
            [name]: value,
        });
    }

    updateStoppedReason(value: string) {
        this.props.onFiltersChange({
            ...this.props.filters,
            stopped_reason: value,
            stopped_by_player: false,
        });
    }

    renderRows() {
        return this.props.logs.data.map((log: ExplorationLogRow) => (
            <tr className="border-t dark:border-gray-700" key={log.id}>
                <td className="p-2">{log.character?.name ?? "—"}</td>
                <td className="p-2">{log.started_at ?? "—"}</td>
                <td className="p-2">{log.ended_at ?? "—"}</td>
                <td className="p-2">{log.fights}</td>
                <td className="p-2">{log.kills}</td>
                <td className="p-2">{log.xp_gained.toLocaleString()}</td>
                <td className="p-2">{log.skill_xp_gained.toLocaleString()}</td>
                <td className="p-2">{log.stopped_reason ?? "—"}</td>
            </tr>
        ));
    }

    render() {
        return (
            <MonitoringCard
                title="Recent Exploration Runs"
                description="Completed and active exploration logs."
            >
                <div id="exploration-logs-table" />
                <div className="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <label className="text-sm font-medium">
                        Character name
                        <input
                            className="mt-1 w-full rounded border border-gray-300 bg-white p-2 text-base dark:border-gray-600 dark:bg-gray-800"
                            type="text"
                            value={this.props.filters.character_name}
                            onChange={(event) =>
                                this.updateFilter(
                                    "character_name",
                                    event.target.value,
                                )
                            }
                        />
                    </label>
                    <label className="text-sm font-medium">
                        Stopped reason
                        <input
                            className="mt-1 w-full rounded border border-gray-300 bg-white p-2 text-base dark:border-gray-600 dark:bg-gray-800"
                            type="text"
                            value={this.props.filters.stopped_reason}
                            onChange={(event) =>
                                this.updateStoppedReason(event.target.value)
                            }
                        />
                    </label>
                    <label className="text-sm font-medium">
                        Date from
                        <input
                            className="mt-1 w-full rounded border border-gray-300 bg-white p-2 text-base dark:border-gray-600 dark:bg-gray-800"
                            type="date"
                            value={this.props.filters.date_from}
                            onChange={(event) =>
                                this.updateFilter(
                                    "date_from",
                                    event.target.value,
                                )
                            }
                        />
                    </label>
                    <label className="text-sm font-medium">
                        Date to
                        <input
                            className="mt-1 w-full rounded border border-gray-300 bg-white p-2 text-base dark:border-gray-600 dark:bg-gray-800"
                            type="date"
                            value={this.props.filters.date_to}
                            onChange={(event) =>
                                this.updateFilter("date_to", event.target.value)
                            }
                        />
                    </label>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[800px] text-left text-sm">
                        <thead>
                            <tr className="border-b dark:border-gray-700">
                                <th scope="col" className="p-2">
                                    Character
                                </th>
                                <th scope="col" className="p-2">
                                    Started
                                </th>
                                <th scope="col" className="p-2">
                                    Ended
                                </th>
                                <th scope="col" className="p-2">
                                    Fights
                                </th>
                                <th scope="col" className="p-2">
                                    Kills
                                </th>
                                <th scope="col" className="p-2">
                                    XP
                                </th>
                                <th scope="col" className="p-2">
                                    Skill XP
                                </th>
                                <th scope="col" className="p-2">
                                    Stopped reason
                                </th>
                            </tr>
                        </thead>
                        <tbody>{this.renderRows()}</tbody>
                    </table>
                    {this.props.logs.data.length === 0 && (
                        <p className="p-4 text-center text-gray-600 dark:text-gray-300">
                            No exploration logs found.
                        </p>
                    )}
                </div>
                <PaginationControls
                    currentPage={this.props.logs.current_page}
                    lastPage={this.props.logs.last_page}
                    onPageChange={this.props.onPageChange}
                />
            </MonitoringCard>
        );
    }
}
