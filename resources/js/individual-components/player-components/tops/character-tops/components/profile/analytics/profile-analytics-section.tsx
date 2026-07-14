import React from "react";
import ProfileAnalyticsSectionProps from "../../../types/profile/analytics/profile-analytics-section-props";
import TopsChartCard from "../sheet-inspect/tops-chart-card";

export default class ProfileAnalyticsSection extends React.Component<ProfileAnalyticsSectionProps> {
    render() {
        return (
            <section
                className="grid gap-4 lg:grid-cols-2"
                aria-label="Analytics"
            >
                <TopsChartCard
                    title="Exploration Kills"
                    description="Cumulative public exploration kills from real dated exploration logs."
                    chart={this.props.analytics?.analytics_kills_chart}
                    xAxisLabel="Date"
                    yAxisLabel="Kills"
                    timeSeries={true}
                />
                <TopsChartCard
                    title="Runs & Completions"
                    description="Cumulative public exploration runs, delve runs, and completed quests over time."
                    chart={this.props.analytics?.analytics_runs_chart}
                    xAxisLabel="Date"
                    yAxisLabel="Count"
                    timeSeries={true}
                />
            </section>
        );
    }
}
