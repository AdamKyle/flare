import React from "react";
import {
    CharacterRow,
    Paginated,
    RequestFiltersType,
    RewardRequest,
} from "../types/reward-queue";
import PaginationControls from "./pagination-controls";
import RequestFilters from "./request-filters";
import RewardQueueCard from "./reward-queue-card";

export default function RequestHistory({
    selectedCharacter,
    requests,
    filters,
    onFiltersChange,
    onClearCharacter,
    onPageChange,
}: {
    selectedCharacter: CharacterRow | null;
    requests: Paginated<RewardRequest>;
    filters: RequestFiltersType;
    onFiltersChange: (filters: RequestFiltersType) => void;
    onClearCharacter: () => void;
    onPageChange: (page: number) => void;
}) {
    return (
        <RewardQueueCard
            title={
                selectedCharacter
                    ? `${selectedCharacter.character_name} request history`
                    : "Global request history"
            }
            description="Completed, failed, processing, and pending requests remain available for inspection."
        >
            {selectedCharacter && (
                <button
                    className="mb-4 rounded border border-gray-300 px-3 py-2 dark:border-gray-600"
                    onClick={onClearCharacter}
                >
                    Show all characters
                </button>
            )}
            <RequestFilters filters={filters} onChange={onFiltersChange} />
            <div className="mt-4 overflow-x-auto">
                <table className="w-full min-w-[850px] text-left text-sm">
                    <thead>
                        <tr>
                            <th className="p-2">Character</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Source</th>
                            <th>Source ID</th>
                            <th>Ledger</th>
                            <th>Messages</th>
                            <th>Failed reason</th>
                            <th>Created</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        {requests.data.map((request) => (
                            <tr
                                className="border-t dark:border-gray-700"
                                key={request.id}
                            >
                                <td className="p-2">
                                    {request.character?.name ??
                                        selectedCharacter?.character_name ??
                                        "—"}
                                </td>
                                <td>{request.status}</td>
                                <td>{request.priority}</td>
                                <td>{request.source_type}</td>
                                <td>{request.source_id ?? "—"}</td>
                                <td>
                                    {request.completed_step_count ?? 0}/
                                    {request.total_step_count ?? 0}
                                </td>
                                <td>{request.un_emitted_message_count ?? 0}</td>
                                <td>{request.failed_reason ?? "—"}</td>
                                <td>{request.created_at}</td>
                                <td>{request.updated_at}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
                {requests.data.length === 0 && (
                    <p className="p-4 text-center text-gray-600 dark:text-gray-300">
                        No requests match these filters.
                    </p>
                )}
            </div>
            <PaginationControls
                currentPage={requests.current_page}
                lastPage={requests.last_page}
                label="requests"
                onPageChange={onPageChange}
            />
        </RewardQueueCard>
    );
}
