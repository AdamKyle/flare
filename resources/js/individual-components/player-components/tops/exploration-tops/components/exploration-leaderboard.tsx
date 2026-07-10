import React from "react";
import TopsDisplayField from "../../shared/types/tops-display-field";
import TopsLeaderboardDashboard from "../../shared/components/tops-leaderboard-dashboard";
import TopsPeriod from "../../shared/types/tops-period";
import ExplorationLeaderboardProps from "../types/exploration-leaderboard-props";

export default class ExplorationLeaderboard extends React.Component<ExplorationLeaderboardProps> {
    tableFields(): TopsDisplayField[] {
        return [
            { key: "kills", label: "Kills", align: "right", type: "number" },
            {
                key: "length_of_time_seconds",
                label: "Length of Time",
                align: "right",
                type: "duration",
            },
            {
                key: "xp_gained",
                label: "XP",
                align: "right",
                type: "number",
            },
            {
                key: "skill_xp_gained",
                label: "Skill XP",
                align: "right",
                type: "number",
            },
        ];
    }

    supportingMetrics(): TopsDisplayField[] {
        return [
            { key: "kills", label: "Kills", align: "right", type: "number" },
            {
                key: "length_of_time_seconds",
                label: "Length of Time",
                align: "right",
                type: "duration",
            },
            {
                key: "xp_gained",
                label: "XP",
                align: "right",
                type: "number",
            },
            {
                key: "skill_xp_gained",
                label: "Skill XP",
                align: "right",
                type: "number",
            },
        ];
    }

    metricType(): TopsDisplayField["type"] {
        if (this.props.metric === "length_of_time_seconds") {
            return "duration";
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
            label: metric?.label ?? "Kills",
            align: "right",
            type: this.metricType(),
        };
    }

    render() {
        return (
            <TopsLeaderboardDashboard
                title="Exploration Leaderboard"
                description="The most active explorers, ranked by kills, length of time, XP, and skill XP."
                resetDescription="This leaderboard tracks live, cumulative character progress and does not reset each month. Current Month and All Time reflect the same up-to-date totals."
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
