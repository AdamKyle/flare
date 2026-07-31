import React from "react";
import { CharacterRow } from "../types/reward-queue";
import CharacterQueueTableProps from "../types/character-queue-table-props";
import PaginationControls from "./pagination-controls";
import RewardQueueCard from "./reward-queue-card";

export default class CharacterQueueTable extends React.Component<CharacterQueueTableProps> {
    renderRows() {
        return this.props.characters.data.map((row: CharacterRow) => (
            <tr
                className="border-t dark:border-gray-700"
                key={row.character_id}
            >
                <td className="p-2 font-medium">{row.character_name}</td>
                <td>{row.battle_requests}</td>
                <td>{row.quest_requests}</td>
                <td>{row.pending_count}</td>
                <td>{row.processing_count}</td>
                <td>{row.resumable_count}</td>
                <td>{row.failed_count}</td>
                <td>{row.completed_count}</td>
                <td>{row.last_request_at}</td>
                <td>
                    <button
                        className="rounded bg-blue-600 px-3 py-2 text-white hover:bg-blue-700"
                        onClick={() => this.props.onSelect(row)}
                    >
                        View requests
                    </button>
                </td>
            </tr>
        ));
    }

    render() {
        return (
            <RewardQueueCard
                title="Character queues"
                description="Aggregated reward request history and current state by character."
            >
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[900px] text-left text-sm">
                        <thead>
                            <tr>
                                <th className="p-2">Character</th>
                                <th>Battle</th>
                                <th>Quest</th>
                                <th>Pending</th>
                                <th>Processing</th>
                                <th>Resumable</th>
                                <th>Failed</th>
                                <th>Completed</th>
                                <th>Last request</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>{this.renderRows()}</tbody>
                    </table>
                    {this.props.characters.data.length === 0 && (
                        <p className="p-4 text-center text-gray-600 dark:text-gray-300">
                            No character reward requests found.
                        </p>
                    )}
                </div>
                <PaginationControls
                    currentPage={this.props.characters.current_page}
                    lastPage={this.props.characters.last_page}
                    label="characters"
                    onPageChange={this.props.onPageChange}
                />
            </RewardQueueCard>
        );
    }
}
