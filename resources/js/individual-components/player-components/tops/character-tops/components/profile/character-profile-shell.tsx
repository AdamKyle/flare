import React from "react";
import CharacterProfileShellProps from "../../types/profile/character-profile-shell-props";
import TopsCharacterInfoTabs from "./sheet-inspect/tops-character-info-tabs";
import TopsCharacterSummaryCard from "./sheet-inspect/tops-character-summary-card";
import CharacterSkillsTabs from "../../../../../../game/sections/character-sheet/components/character-skills-tabs";
import CharacterClassRanks from "../../../../../../game/sections/character-sheet/components/character-class-ranks";
import ProfileWornItems from "./equipment/profile-equipped-items";
import DangerButton from "../../../../../../game/components/ui/buttons/danger-button";
import ProfileActivitySection from "./activity/profile-activity-section";
import ProfileAnalyticsSection from "./analytics/profile-analytics-section";
import ProfileQuestsSection from "./quests/profile-quests-section";
import ProfileKingdoms from "../profile-kingdoms";
import ProfileStatsSection from "./stats/profile-stats-section";

export default class CharacterProfileShell extends React.Component<CharacterProfileShellProps> {
    state = { showAdditionalDetails: false };

    render() {
        const profile = this.props.profile;
        const overview = profile.overview ?? {};
        const skills = profile.skills ?? {};

        return (
            <div className="space-y-4">
                <div className="space-y-4">
                    <div className="grid gap-4 xl:grid-cols-2">
                        <TopsCharacterInfoTabs
                            profile={profile}
                            manage_addition_data={() =>
                                this.setState({
                                    showAdditionalDetails: true,
                                })
                            }
                        />
                        <TopsCharacterSummaryCard profile={profile} />
                    </div>
                    <div className="grid gap-4 xl:grid-cols-2">
                        <CharacterSkillsTabs
                            character_id={overview.id ?? 0}
                            user_id={0}
                            is_dead={false}
                            is_automation_running={false}
                            is_faction_loyalty_automation_running={false}
                            is_delve_running={false}
                            active_automation={null}
                            finished_loading={true}
                            read_only={true}
                            preloaded_skills={{
                                training_skills: skills.regular_skills ?? [],
                                crafting_skills: skills.crafting_skills ?? [],
                            }}
                            preloaded_kingdom_passives={
                                profile.kingdom_passives ?? []
                            }
                        />
                        <ProfileWornItems
                            equipment={profile.equipment}
                            character_id={overview.id ?? 0}
                        />
                    </div>
                </div>
                {this.state.showAdditionalDetails ? (
                    <section
                        className="space-y-4"
                        aria-label="Additional character details"
                    >
                        <DangerButton
                            button_label="Hide Additional Details"
                            on_click={() =>
                                this.setState({ showAdditionalDetails: false })
                            }
                        />
                        <ProfileStatsSection stats={profile.stats} />
                        <CharacterClassRanks
                            character={overview}
                            read_only={true}
                            preloaded_class_ranks={profile.class_ranks ?? []}
                            preloaded_class_ranks_offered={
                                profile.class_ranks_offered ?? []
                            }
                        />
                    </section>
                ) : null}
                <section
                    aria-label="Additional public profile details"
                    className="space-y-4"
                >
                    <ProfileQuestsSection quests={this.props.profile.quests} />
                    <ProfileKingdoms kingdoms={this.props.profile.kingdoms} />
                    <ProfileAnalyticsSection
                        analytics={this.props.profile.analytics}
                    />
                    <ProfileActivitySection
                        activity={this.props.profile.activity}
                    />
                </section>
            </div>
        );
    }
}
