import React from "react";
import BasicCard from "../../../../../game/components/ui/cards/basic-card";
import TopsStatList from "../../shared/components/tops-stat-list";
import ProfileSectionProps from "../types/profile-section-props";
import { asTopsRecord } from "../../shared/helpers/tops-value-helpers";
import TopsStatListItem from "../../shared/types/tops-stat-list-item";
import TopsValue from "../../shared/types/tops-value";

export default class ProfileActivity extends React.Component<ProfileSectionProps> {
    activity(): Record<string, TopsValue> {
        return this.props.activity ?? {};
    }

    loginActivityItems(): TopsStatListItem[] {
        const activity = this.activity();

        return [
            { label: "Last Login", value: activity.last_login_at },
            { label: "Last Activity", value: activity.last_activity_at },
            {
                label: "Total Login Duration 7 Days",
                value: activity.login_duration_7_days,
            },
            {
                label: "Total Login Duration 14 Days",
                value: activity.login_duration_14_days,
            },
            {
                label: "Total Login Duration 30 Days",
                value: activity.login_duration_30_days,
            },
            { label: "Login Count 7 Days", value: activity.login_count_7_days },
            {
                label: "Login Count 14 Days",
                value: activity.login_count_14_days,
            },
            {
                label: "Login Count 30 Days",
                value: activity.login_count_30_days,
            },
        ];
    }

    gameActivityItems(): TopsStatListItem[] {
        const activity = this.activity();

        return [
            {
                label: "Exploration Run Count",
                value: activity.exploration_run_count,
            },
            { label: "Exploration Kills", value: activity.exploration_kills },
            { label: "Delve Run Count", value: activity.delve_run_count },
            {
                label: "Faction Loyalty Automation Count",
                value: activity.faction_loyalty_automation_count,
            },
            {
                label: "Latest Faction Loyalty Action",
                value: activity.latest_faction_loyalty_action,
            },
            {
                label: "Latest Faction Loyalty Outcome",
                value: activity.latest_faction_loyalty_outcome,
            },
            {
                label: "Quest Completion Count",
                value: activity.quest_completion_count,
            },
            {
                label: "Guide Quest Completion Count",
                value: activity.guide_quest_completion_count,
            },
        ];
    }

    delveOutcomeItems(): TopsStatListItem[] {
        const activity = this.activity();
        const delveOutcomeCounts = asTopsRecord(activity.delve_outcome_counts);

        return Object.keys(delveOutcomeCounts).map((key: string) => ({
            label: key || "Unknown Outcome",
            value: delveOutcomeCounts[key],
        }));
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
                        <TopsStatList items={this.loginActivityItems()} />
                    </div>
                </BasicCard>
                <BasicCard>
                    <h2 className="text-xl font-semibold">Game Activity</h2>
                    <div className="mt-4">
                        <TopsStatList items={this.gameActivityItems()} />
                    </div>
                </BasicCard>
                <BasicCard additionalClasses="lg:col-span-2">
                    <h2 className="text-xl font-semibold">Delve Outcomes</h2>
                    <div className="mt-4">
                        <TopsStatList items={this.delveOutcomeItems()} />
                    </div>
                </BasicCard>
            </section>
        );
    }
}
