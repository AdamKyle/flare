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
                label: "Total Enemy Strength %",
                align: "right",
                type: "percent",
            },
            {
                key: "survived_duration_seconds",
                label: "Survived Duration",
                align: "right",
                type: "duration",
            },
            {
                key: "total_floors",
                label: "Total Floors",
                align: "right",
                type: "number",
            },
        ];
    }

    supportingMetrics(): TopsDisplayField[] {
        return [
            {
                key: "strongest_enemy_increase",
                label: "Enemy Strength",
                align: "right",
                type: "percent",
            },
            {
                key: "survived_duration_seconds",
                label: "Duration",
                align: "right",
                type: "duration",
            },
            {
                key: "total_floors",
                label: "Floors",
                align: "right",
                type: "number",
            },
        ];
    }

    metricType(): TopsDisplayField["type"] {
        if (this.props.metric === "survived_duration_seconds") {
            return "duration";
        }

        if (this.props.metric === "strongest_enemy_increase") {
            return "percent";
        }

        return "number";
    }

    primaryMetric(): TopsDisplayField {
        const metric = this.props.leaderboard?.available_metrics.find(
            (availableMetric: TopsPeriod) =>
                availableMetric.key === this.props.metric,
        );

        return {
            key: this.props.metric,
            label: metric?.label ?? "Survived Duration",
            align: "right",
            type: this.metricType(),
        };
    }

    render() {
        return (
            <TopsLeaderboardDashboard
                title="Delve Leaderboard"
                description="Ranked by survived duration."
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
