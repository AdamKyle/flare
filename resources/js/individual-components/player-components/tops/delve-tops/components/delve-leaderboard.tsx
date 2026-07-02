import React from "react";
import TopsDisplayField from "../../shared/types/tops-display-field";
import TopsLeaderboardDashboard from "../../shared/components/tops-leaderboard-dashboard";
import TopsPeriod from "../../shared/types/tops-period";
import DelveLeaderboardProps from "../types/delve-leaderboard-props";

export default class DelveLeaderboard extends React.Component<DelveLeaderboardProps> {
    tableFields(): TopsDisplayField[] {
        return [
            {
                key: "strongest_enemy_increase",
                label: "Strongest Enemy Increase",
                align: "right",
                type: "number",
            },
            {
                key: "encounter_count",
                label: "Encounters",
                align: "right",
                type: "number",
            },
            { key: "run_count", label: "Runs", align: "right", type: "number" },
            {
                key: "average_pack_size",
                label: "Average Pack Size",
                align: "right",
                type: "number",
            },
            {
                key: "survived_count",
                label: "Survived",
                align: "right",
                type: "number",
            },
            {
                key: "latest_run_started_at",
                label: "Latest Run",
                align: "left",
                type: "date",
            },
        ];
    }

    supportingMetrics(): TopsDisplayField[] {
        return [
            {
                key: "strongest_enemy_increase",
                label: "Strongest Enemy Increase",
                align: "right",
                type: "number",
            },
            {
                key: "encounter_count",
                label: "Encounters",
                align: "right",
                type: "number",
            },
            { key: "run_count", label: "Runs", align: "right", type: "number" },
            {
                key: "average_pack_size",
                label: "Average Pack Size",
                align: "right",
                type: "number",
            },
        ];
    }

    primaryMetric(): TopsDisplayField {
        const metric = this.props.leaderboard?.available_metrics.find(
            (availableMetric: TopsPeriod) =>
                availableMetric.key === this.props.metric,
        );

        return {
            key: this.props.metric,
            label: metric?.label ?? "Strongest Enemy Increase",
            align: "right",
            type: "number",
        };
    }

    render() {
        return (
            <TopsLeaderboardDashboard
                title="Delve Leaderboard"
                description="The strongest delve runs, ranked by enemy strength, encounters, runs, and pack size."
                resetDescription="Leaderboards reset on the first day of each calendar month. Current Month is shown by default, previous monthly results stay available through archived snapshots, and All Time keeps lifetime totals."
                response={this.props.leaderboard}
                primaryMetric={this.primaryMetric()}
                supportingMetrics={this.supportingMetrics()}
                tableFields={this.tableFields()}
                period={this.props.period}
                metric={this.props.metric}
                search={this.props.search}
                loading={this.props.loading}
                errorMessage={this.props.errorMessage}
                onPeriodChange={this.props.onPeriodChange}
                onMetricChange={this.props.onMetricChange}
                onSearch={this.props.onSearch}
            />
        );
    }
}
