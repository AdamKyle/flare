import React from "react";
import BasicCard from "../../../../../game/components/ui/cards/basic-card";
import TopsStatList from "../../shared/components/tops-stat-list";
import TopsEmptyState from "../../shared/components/tops-empty-state";
import { formatTopsValue } from "../../shared/helpers/tops-format-value";
import ProfileSectionProps from "../types/profile-section-props";
import TopsValue from "../../shared/types/tops-value";
import {
    asTopsRecord,
    asTopsRecordList,
} from "../../shared/helpers/tops-value-helpers";
import TopsStatListItem from "../../shared/types/tops-stat-list-item";

export default class ProfileAnalytics extends React.Component<ProfileSectionProps> {
    analytics(): Record<string, TopsValue> {
        return this.props.analytics ?? {};
    }

    analyticsSummaryItems(): TopsStatListItem[] {
        const summary = asTopsRecord(this.analytics().summary);

        return [
            {
                label: "Exploration Kills",
                value: summary.exploration_kills,
            },
            {
                label: "Exploration Runs",
                value: summary.exploration_runs,
            },
            { label: "Delve Runs", value: summary.delve_runs },
            {
                label: "Quests Completed",
                value: summary.quests_completed,
            },
        ];
    }

    explorationRows(): Record<string, TopsValue>[] {
        const tables = asTopsRecord(this.analytics().tables);

        return asTopsRecordList(tables.exploration_by_day);
    }

    renderExplorationRow(row: Record<string, TopsValue>) {
        return (
            <tr key={String(row.date)}>
                <td className="border-b border-gray-100 py-2 dark:border-gray-800">
                    {row.date}
                </td>
                <td className="border-b border-gray-100 py-2 text-right tabular-nums dark:border-gray-800">
                    {formatTopsValue(row.kills ?? 0)}
                </td>
                <td className="border-b border-gray-100 py-2 text-right tabular-nums dark:border-gray-800">
                    {formatTopsValue(row.runs ?? 0)}
                </td>
            </tr>
        );
    }

    render() {
        return (
            <section className="grid gap-4" aria-label="Analytics">
                <BasicCard>
                    <h2 className="text-xl font-semibold">Summary</h2>
                    <div className="mt-4">
                        <TopsStatList items={this.analyticsSummaryItems()} />
                    </div>
                </BasicCard>
                <BasicCard>
                    <h2 className="text-xl font-semibold">
                        Exploration by Day
                    </h2>
                    <p className="mt-1 text-sm text-gray-700 dark:text-gray-300">
                        This table is shown only from real exploration log
                        timestamps.
                    </p>
                    <div className="mt-4 overflow-x-auto">
                        {this.explorationRows().length === 0 ? (
                            <TopsEmptyState message="No time-series exploration analytics are available for this character." />
                        ) : (
                            <table className="w-full min-w-full text-left text-sm">
                                <thead>
                                    <tr>
                                        <th className="border-b border-gray-200 py-2 dark:border-gray-700">
                                            Date
                                        </th>
                                        <th className="border-b border-gray-200 py-2 text-right dark:border-gray-700">
                                            Kills
                                        </th>
                                        <th className="border-b border-gray-200 py-2 text-right dark:border-gray-700">
                                            Runs
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {this.explorationRows().map(
                                        (row: Record<string, TopsValue>) =>
                                            this.renderExplorationRow(row),
                                    )}
                                </tbody>
                            </table>
                        )}
                    </div>
                </BasicCard>
            </section>
        );
    }
}
