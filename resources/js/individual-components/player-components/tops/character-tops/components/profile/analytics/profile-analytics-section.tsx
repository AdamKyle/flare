import React from "react";
import BasicCard from "../../../../../../../game/components/ui/cards/basic-card";
import { formatTopsValue } from "../../../../shared/helpers/tops-format-value";
import {
    asTopsRecord,
    asTopsRecordList,
} from "../../../../shared/helpers/tops-value-helpers";
import TopsValue from "../../../../shared/types/tops-value";
import ProfileAnalyticsSectionProps from "../../../types/profile/analytics/profile-analytics-section-props";
import TopsChartCard from "../sheet-inspect/tops-chart-card";

export default class ProfileAnalyticsSection extends React.Component<ProfileAnalyticsSectionProps> {
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
                <TopsChartCard
                    title="Analytics Summary"
                    description="Public activity totals returned in the Tops profile payload."
                    chart={this.props.analytics?.analytics_summary_chart}
                    xAxisLabel="Metric"
                    yAxisLabel="Count"
                />
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
                                            <dl className="mt-2 grid gap-2 sm:grid-cols-2">
                                                <div>
                                                    <dt className="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                                        Runs
                                                    </dt>
                                                    <dd>
                                                        {formatTopsValue(
                                                            row.runs,
                                                        )}
                                                    </dd>
                                                </div>
                                                <div>
                                                    <dt className="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                                        Kills
                                                    </dt>
                                                    <dd>
                                                        {formatTopsValue(
                                                            row.kills,
                                                        )}
                                                    </dd>
                                                </div>
                                            </dl>
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
