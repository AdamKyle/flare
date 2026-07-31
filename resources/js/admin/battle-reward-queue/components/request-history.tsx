import React from "react";
import { RewardRequest } from "../types/reward-queue";
import RequestHistoryProps from "../types/request-history-props";
import PaginationControls from "./pagination-controls";
import RequestFilters from "./request-filters";
import RewardQueueCard from "./reward-queue-card";

export default class RequestHistory extends React.Component<RequestHistoryProps> {
    title() {
        if (this.props.selectedCharacter) {
            return `${this.props.selectedCharacter.character_name} request history`;
        }

        return "Global request history";
    }

    renderRows() {
        return this.props.requests.data.map((request: RewardRequest) => (
            <tr className="border-t dark:border-gray-700" key={request.id}>
                <td className="p-2">
                    {request.character?.name ??
                        this.props.selectedCharacter?.character_name ??
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
        ));
    }

    render() {
        return (
            <RewardQueueCard
                title={this.title()}
                description="Completed, failed, processing, and pending requests remain available for inspection."
            >
                {this.props.selectedCharacter && (
                    <button
                        className="mb-4 rounded border border-gray-300 px-3 py-2 dark:border-gray-600"
                        onClick={this.props.onClearCharacter}
                    >
                        Show all characters
                    </button>
                )}
                <RequestFilters
                    filters={this.props.filters}
                    onChange={this.props.onFiltersChange}
                />
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
                        <tbody>{this.renderRows()}</tbody>
                    </table>
                    {this.props.requests.data.length === 0 && (
                        <p className="p-4 text-center text-gray-600 dark:text-gray-300">
                            No requests match these filters.
                        </p>
                    )}
                </div>
                <PaginationControls
                    currentPage={this.props.requests.current_page}
                    lastPage={this.props.requests.last_page}
                    label="requests"
                    onPageChange={this.props.onPageChange}
                />
            </RewardQueueCard>
        );
    }
}
