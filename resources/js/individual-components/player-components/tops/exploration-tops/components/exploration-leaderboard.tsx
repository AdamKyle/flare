import React from "react";
import TopsDisplayField from "../../shared/types/tops-display-field";
import TopsLeaderboardDashboard from "../../shared/components/tops-leaderboard-dashboard";
import TopsPeriod from "../../shared/types/tops-period";
import ExplorationLeaderboardProps from "../types/exploration-leaderboard-props";

export default class ExplorationLeaderboard extends React.Component<ExplorationLeaderboardProps> {
    tableFields(): TopsDisplayField[] {
        return [
            { key: "kills", label: "Kills", align: "right", type: "number" },
            { key: "fights", label: "Fights", align: "right", type: "number" },
            {
                key: "xp_gained",
                label: "XP Gained",
                align: "right",
                type: "number",
            },
            {
                key: "skill_xp_gained",
                label: "Skill XP",
                align: "right",
                type: "number",
            },
            { key: "run_count", label: "Runs", align: "right", type: "number" },
            {
                key: "latest_started_at",
                label: "Latest Started",
                align: "left",
                type: "date",
            },
        ];
    }

    supportingMetrics(): TopsDisplayField[] {
        return [
            { key: "kills", label: "Kills", align: "right", type: "number" },
            { key: "fights", label: "Fights", align: "right", type: "number" },
            {
                key: "xp_gained",
                label: "XP Gained",
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

    primaryMetric(): TopsDisplayField {
        const metric = this.props.leaderboard?.available_metrics.find(
            (availableMetric: TopsPeriod) =>
                availableMetric.key === this.props.metric,
        );

        return {
            key: this.props.metric,
            label: metric?.label ?? "Kills",
            align: "right",
            type: "number",
        };
    }

    render() {
        return (
            <TopsLeaderboardDashboard
                title="Exploration Leaderboard"
                description="The most active explorers, ranked by kills, fights, XP, skill XP, and runs."
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
