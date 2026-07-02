import React from "react";
import TopsProfileTabs from "../../../shared/components/tops-profile-tabs";
import ProfileSkills from "../profile-skills";
import ProfileFactions from "../profile-factions";
import ProfileReincarnation from "../profile-reincarnation";
import ProfileKingdoms from "../profile-kingdoms";
import ProfileOverview from "../profile-overview";
import CharacterProfileHeader from "./character-profile-header";
import CharacterProfileSidePanel from "./character-profile-side-panel";
import ProfileWornItems from "./equipment/profile-equipped-items";
import ProfileActivitySection from "./activity/profile-activity-section";
import ProfileAnalyticsSection from "./analytics/profile-analytics-section";
import ProfileQuestsSection from "./quests/profile-quests-section";
import ProfileClassMasteriesSection from "./skills/profile-class-masteries-section";
import ProfileStatsSection from "./stats/profile-stats-section";
import CharacterProfileShellProps from "../../types/profile/character-profile-shell-props";

export default class CharacterProfileShell extends React.Component<CharacterProfileShellProps> {
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
                return <ProfileStatsSection stats={this.props.profile.stats} />;
            case "equipment":
                return (
                    <ProfileWornItems
                        equipment={this.props.profile.equipment}
                    />
                );
            case "skills":
                return (
                    <div className="grid gap-4">
                        <ProfileSkills skills={this.props.profile.skills} />
                        <ProfileClassMasteriesSection
                            skills={this.props.profile.skills}
                        />
                    </div>
                );
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
                    <ProfileActivitySection
                        activity={this.props.profile.activity}
                    />
                );
            case "quests":
                return (
                    <ProfileQuestsSection quests={this.props.profile.quests} />
                );
            case "kingdoms":
                return (
                    <ProfileKingdoms kingdoms={this.props.profile.kingdoms} />
                );
            case "analytics":
                return (
                    <ProfileAnalyticsSection
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
        return (
            <div className="space-y-4">
                <CharacterProfileHeader
                    overview={this.props.profile.overview ?? {}}
                />
                <div className="grid gap-4 xl:grid-cols-[minmax(0,1fr)_320px]">
                    <main className="min-w-0 space-y-4">
                        <TopsProfileTabs
                            tabs={this.tabs()}
                            active={this.props.activeTab}
                            onChange={this.props.onTabChange}
                        />
                        <div aria-live="polite">{this.renderActivePanel()}</div>
                    </main>
                    <CharacterProfileSidePanel profile={this.props.profile} />
                </div>
            </div>
        );
    }
}
