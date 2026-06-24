import React from "react";
import {
    ExplorationFilters,
    ExplorationLogRow,
    Paginated,
} from "../types/exploration-monitoring";
import MonitoringCard from "./monitoring-card";

function PaginationControls({
    currentPage,
    lastPage,
    onPageChange,
}: {
    currentPage: number;
    lastPage: number;
    onPageChange: (page: number) => void;
}) {
    return (
        <div className="mt-4 flex flex-wrap items-center justify-between gap-2">
            <span className="text-sm text-gray-600 dark:text-gray-300">
                Page {currentPage} of {lastPage}
            </span>
            <div className="flex gap-2">
                <button
                    className="rounded border border-gray-300 px-3 py-2 text-sm disabled:opacity-50 dark:border-gray-600"
                    disabled={currentPage <= 1}
                    onClick={() => onPageChange(currentPage - 1)}
                >
                    Previous
                </button>
                <button
                    className="rounded border border-gray-300 px-3 py-2 text-sm disabled:opacity-50 dark:border-gray-600"
                    disabled={currentPage >= lastPage}
                    onClick={() => onPageChange(currentPage + 1)}
                >
                    Next
                </button>
            </div>
        </div>
    );
}

export default function ExplorationLogsTable({
    logs,
    filters,
    onFiltersChange,
    onPageChange,
}: {
    logs: Paginated<ExplorationLogRow>;
    filters: ExplorationFilters;
    onFiltersChange: (filters: ExplorationFilters) => void;
    onPageChange: (page: number) => void;
}) {
    return (
        <MonitoringCard
            title="Recent Exploration Runs"
            description="Completed and active exploration logs."
        >
            <div className="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <label className="text-sm font-medium">
                    Character name
                    <input
                        className="mt-1 w-full rounded border border-gray-300 bg-white p-2 dark:border-gray-600 dark:bg-gray-800"
                        type="text"
                        value={filters.character_name}
                        onChange={(e) =>
                            onFiltersChange({
                                ...filters,
                                character_name: e.target.value,
                            })
                        }
                    />
                </label>
                <label className="text-sm font-medium">
                    Stopped reason
                    <input
                        className="mt-1 w-full rounded border border-gray-300 bg-white p-2 dark:border-gray-600 dark:bg-gray-800"
                        type="text"
                        value={filters.stopped_reason}
                        onChange={(e) =>
                            onFiltersChange({
                                ...filters,
                                stopped_reason: e.target.value,
                            })
                        }
                    />
                </label>
                <label className="text-sm font-medium">
                    Date from
                    <input
                        className="mt-1 w-full rounded border border-gray-300 bg-white p-2 dark:border-gray-600 dark:bg-gray-800"
                        type="date"
                        value={filters.date_from}
                        onChange={(e) =>
                            onFiltersChange({
                                ...filters,
                                date_from: e.target.value,
                            })
                        }
                    />
                </label>
                <label className="text-sm font-medium">
                    Date to
                    <input
                        className="mt-1 w-full rounded border border-gray-300 bg-white p-2 dark:border-gray-600 dark:bg-gray-800"
                        type="date"
                        value={filters.date_to}
                        onChange={(e) =>
                            onFiltersChange({
                                ...filters,
                                date_to: e.target.value,
                            })
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
                    <tbody>
                        {logs.data.map((log) => (
                            <tr
                                className="border-t dark:border-gray-700"
                                key={log.id}
                            >
                                <td className="p-2">
                                    {log.character?.name ?? "—"}
                                </td>
                                <td className="p-2">{log.started_at ?? "—"}</td>
                                <td className="p-2">{log.ended_at ?? "—"}</td>
                                <td className="p-2">{log.fights}</td>
                                <td className="p-2">{log.kills}</td>
                                <td className="p-2">
                                    {log.xp_gained.toLocaleString()}
                                </td>
                                <td className="p-2">
                                    {log.skill_xp_gained.toLocaleString()}
                                </td>
                                <td className="p-2">
                                    {log.stopped_reason ?? "—"}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
                {logs.data.length === 0 && (
                    <p className="p-4 text-center text-gray-600 dark:text-gray-300">
                        No exploration logs found.
                    </p>
                )}
            </div>
            <PaginationControls
                currentPage={logs.current_page}
                lastPage={logs.last_page}
                onPageChange={onPageChange}
            />
        </MonitoringCard>
    );
}
