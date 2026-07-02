import React from "react";
import TopsProfileTabs from "../../shared/components/tops-profile-tabs";
import BasicCard from "../../../../../game/components/ui/cards/basic-card";
import ProfileOverview from "./profile-overview";
import ProfileStats from "./profile-stats";
import ProfileEquipment from "./profile-equipment";
import ProfileSkills from "./profile-skills";
import ProfileFactions from "./profile-factions";
import ProfileReincarnation from "./profile-reincarnation";
import ProfileActivity from "./profile-activity";
import ProfileQuests from "./profile-quests";
import ProfileKingdoms from "./profile-kingdoms";
import ProfileAnalytics from "./profile-analytics";
import CharacterProfileProps from "../types/character-profile-props";
import { asTopsRecord } from "../../shared/helpers/tops-value-helpers";
import TopsValue from "../../shared/types/tops-value";

export default class CharacterProfile extends React.Component<CharacterProfileProps> {
    overview(): Record<string, TopsValue> {
        return this.props.profile.overview ?? {};
    }

    reincarnation(): Record<string, TopsValue> {
        return asTopsRecord(this.props.profile.reincarnation);
    }

    tabs(): string[] {
        return [
            "overview",
            "stats",
            "equipment",
            "skills",
            "factions",
            "reincarnation",
            "activity",
            "quests",
            "kingdoms",
            "analytics",
        ];
    }

    renderActivePanel() {
        switch (this.props.activeTab) {
            case "stats":
                return <ProfileStats stats={this.props.profile.stats} />;
            case "equipment":
                return (
                    <ProfileEquipment
                        equipment={this.props.profile.equipment}
                    />
                );
            case "skills":
                return <ProfileSkills skills={this.props.profile.skills} />;
            case "factions":
                return (
                    <ProfileFactions factions={this.props.profile.factions} />
                );
            case "reincarnation":
                return (
                    <ProfileReincarnation
                        reincarnation={this.props.profile.reincarnation}
                    />
                );
            case "activity":
                return (
                    <ProfileActivity activity={this.props.profile.activity} />
                );
            case "quests":
                return <ProfileQuests quests={this.props.profile.quests} />;
            case "kingdoms":
                return (
                    <ProfileKingdoms kingdoms={this.props.profile.kingdoms} />
                );
            case "analytics":
                return (
                    <ProfileAnalytics
                        analytics={this.props.profile.analytics}
                    />
                );
            default:
                return (
                    <ProfileOverview overview={this.props.profile.overview} />
                );
        }
    }

    render() {
        const overview = this.overview();
        const reincarnation = this.reincarnation();

        return (
            <div className="space-y-4">
                <BasicCard>
                    <div className="grid gap-4 lg:grid-cols-3">
                        <div className="lg:col-span-2">
                            <h2 className="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                {overview.name ?? "Character"}
                            </h2>
                            <p className="mt-1 text-gray-700 dark:text-gray-300">
                                Level {overview.level ?? 0}{" "}
                                {overview.race ?? "Unknown Race"}{" "}
                                {overview.class ?? "Unknown Class"}
                            </p>
                        </div>
                        <dl className="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt className="font-semibold text-gray-700 dark:text-gray-300">
                                    Reincarnations
                                </dt>
                                <dd>{reincarnation.times_reincarnated ?? 0}</dd>
                            </div>
                            <div>
                                <dt className="font-semibold text-gray-700 dark:text-gray-300">
                                    Status
                                </dt>
                                <dd>
                                    {overview.online ? "Online" : "Offline"}
                                </dd>
                            </div>
                            <div>
                                <dt className="font-semibold text-gray-700 dark:text-gray-300">
                                    Map
                                </dt>
                                <dd>{overview.current_map ?? "Unknown"}</dd>
                            </div>
                            <div>
                                <dt className="font-semibold text-gray-700 dark:text-gray-300">
                                    Last Active
                                </dt>
                                <dd>{overview.last_active_at ?? "Unknown"}</dd>
                            </div>
                        </dl>
                    </div>
                </BasicCard>
                <TopsProfileTabs
                    tabs={this.tabs()}
                    active={this.props.activeTab}
                    onChange={this.props.onTabChange}
                />
                <div aria-live="polite">{this.renderActivePanel()}</div>
            </div>
        );
    }
}
