import React from "react";
import BasicCard from "../../../../../../../game/components/ui/cards/basic-card";
import TopsStatList from "../../../../shared/components/tops-stat-list";
import { formatTopsValue } from "../../../../shared/helpers/tops-format-value";
import { asTopsRecord } from "../../../../shared/helpers/tops-value-helpers";
import TopsStatListItem from "../../../../shared/types/tops-stat-list-item";
import TopsValue from "../../../../shared/types/tops-value";
import ProfileActivitySectionProps from "../../../types/profile/activity/profile-activity-section-props";

export default class ProfileActivitySection extends React.Component<ProfileActivitySectionProps> {
    activity(): Record<string, TopsValue> {
        return this.props.activity ?? {};
    }

    loginItems(): TopsStatListItem[] {
        const activity = this.activity();

        return [
            { label: "Last Login", value: activity.last_login_at },
            { label: "Last Activity", value: activity.last_activity_at },
            {
                label: "Login Duration 7 Days",
                value: activity.login_duration_7_days,
            },
            {
                label: "Login Duration 14 Days",
                value: activity.login_duration_14_days,
            },
            {
                label: "Login Duration 30 Days",
                value: activity.login_duration_30_days,
            },
        ];
    }

    gameItems(): TopsStatListItem[] {
        const activity = this.activity();

        return [
            {
                label: "Exploration Runs",
                value: activity.exploration_run_count,
            },
            { label: "Exploration Kills", value: activity.exploration_kills },
            { label: "Delve Runs", value: activity.delve_run_count },
            {
                label: "Faction Automation Count",
                value: activity.faction_loyalty_automation_count,
            },
            {
                label: "Latest Faction Action",
                value: activity.latest_faction_loyalty_action,
            },
            {
                label: "Latest Faction Outcome",
                value: activity.latest_faction_loyalty_outcome,
            },
        ];
    }

    renderDelveOutcomes() {
        const outcomes = asTopsRecord(this.activity().delve_outcome_counts);

        return (
            <BasicCard additionalClasses="lg:col-span-2">
                <h2 className="text-xl font-semibold">Delve Outcomes</h2>
                <div className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    {Object.keys(outcomes).length === 0 ? (
                        <p className="text-sm text-gray-700 dark:text-gray-300">
                            No public delve outcomes are available.
                        </p>
                    ) : (
                        Object.keys(outcomes).map((key: string) => (
                            <div
                                key={key}
                                className="rounded-sm border border-gray-200 p-3 dark:border-gray-700"
                            >
                                <p className="text-sm font-semibold">{key}</p>
                                <p className="text-2xl font-bold tabular-nums">
                                    {formatTopsValue(outcomes[key])}
                                </p>
                            </div>
                        ))
                    )}
                </div>
            </BasicCard>
        );
    }

    render() {
        return (
            <section
                className="grid gap-4 lg:grid-cols-2"
                aria-label="Activity"
            >
                <BasicCard>
                    <h2 className="text-xl font-semibold">Login Activity</h2>
                    <div className="mt-4">
                        <TopsStatList items={this.loginItems()} />
                    </div>
                </BasicCard>
                <BasicCard>
                    <h2 className="text-xl font-semibold">Game Activity</h2>
                    <div className="mt-4">
                        <TopsStatList items={this.gameItems()} />
                    </div>
                </BasicCard>
                {this.renderDelveOutcomes()}
            </section>
        );
    }
}
