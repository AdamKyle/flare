import React from "react";
import BasicCard from "../../../../../../../game/components/ui/cards/basic-card";
import TopsStatList from "../../../../shared/components/tops-stat-list";
import { formatTopsValue } from "../../../../shared/helpers/tops-format-value";
import {
    asTopsRecord,
    asTopsRecordList,
} from "../../../../shared/helpers/tops-value-helpers";
import TopsStatListItem from "../../../../shared/types/tops-stat-list-item";
import TopsValue from "../../../../shared/types/tops-value";
import ProfileAnalyticsSectionProps from "../../../types/profile/analytics/profile-analytics-section-props";

export default class ProfileAnalyticsSection extends React.Component<ProfileAnalyticsSectionProps> {
    summaryItems(): TopsStatListItem[] {
        const summary = asTopsRecord(this.props.analytics?.summary);

        return [
            { label: "Exploration Kills", value: summary.exploration_kills },
            { label: "Exploration Runs", value: summary.exploration_runs },
            { label: "Delve Runs", value: summary.delve_runs },
            { label: "Quests Completed", value: summary.quests_completed },
        ];
    }

    explorationRows(): Record<string, TopsValue>[] {
        const tables = asTopsRecord(this.props.analytics?.tables);

        return asTopsRecordList(tables.exploration_by_day);
    }

    render() {
        const explorationRows = this.explorationRows();

        return (
            <section
                className="grid gap-4 lg:grid-cols-2"
                aria-label="Analytics"
            >
                <BasicCard>
                    <h2 className="text-xl font-semibold">Analytics Summary</h2>
                    <div className="mt-4">
                        <TopsStatList items={this.summaryItems()} />
                    </div>
                </BasicCard>
                <BasicCard>
                    <h2 className="text-xl font-semibold">
                        Exploration By Day
                    </h2>
                    <div className="mt-4 max-h-96 overflow-y-auto">
                        {explorationRows.length === 0 ? (
                            <p className="text-sm text-gray-700 dark:text-gray-300">
                                No public exploration analytics are available.
                            </p>
                        ) : (
                            <div className="grid gap-2">
                                {explorationRows.map(
                                    (
                                        row: Record<string, TopsValue>,
                                        index: number,
                                    ) => (
                                        <article
                                            key={String(row.date) + index}
                                            className="rounded-sm border border-gray-200 p-3 dark:border-gray-700"
                                        >
                                            <p className="font-semibold">
                                                {row.date}
                                            </p>
                                            <p className="text-sm text-gray-700 dark:text-gray-300">
                                                Runs {formatTopsValue(row.runs)}{" "}
                                                · Kills{" "}
                                                {formatTopsValue(row.kills)}
                                            </p>
                                        </article>
                                    ),
                                )}
                            </div>
                        )}
                    </div>
                </BasicCard>
            </section>
        );
    }
}
