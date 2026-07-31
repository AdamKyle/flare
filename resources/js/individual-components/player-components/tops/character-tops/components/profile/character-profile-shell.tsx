import React from "react";
import CharacterProfileShellProps from "../../types/profile/character-profile-shell-props";
import TopsCharacterInfoTabs from "./sheet-inspect/tops-character-info-tabs";
import TopsCharacterSummaryCard from "./sheet-inspect/tops-character-summary-card";
import CharacterSkillsTabs from "../../../../../../game/sections/character-sheet/components/character-skills-tabs";
import AdditionalStatSection from "../../../../../../game/components/character-sheet/additional-stats-section/additional-stat-section";
import ProfileWornItems from "./equipment/profile-equipped-items";
import DangerButton from "../../../../../../game/components/ui/buttons/danger-button";
import ProfileActivitySection from "./activity/profile-activity-section";
import ProfileAnalyticsSection from "./analytics/profile-analytics-section";
import ProfileQuestsSection from "./quests/profile-quests-section";
import ProfileKingdoms from "../profile-kingdoms";
import TopsLoadingState from "../../../shared/components/tops-loading-state";
import TopsErrorState from "../../../shared/components/tops-error-state";
import { CharacterType } from "../../../../../../game/lib/game/character/character-type";
import StatDetailsType from "../../../../../../game/components/character-sheet/additional-stats-section/types/stat-details-type";
import ResistanceInfoType from "../../../../../../game/components/character-sheet/additional-stats-section/types/resistance-info-type";
import ElementalAtonementType from "../../../../../../game/components/character-sheet/additional-stats-section/types/elemental-atonement-type";
import ReincarnationDetailsType from "../../../../../../game/components/character-sheet/additional-stats-section/types/reincarnation-details-type";
import TopsValue from "../../../shared/types/tops-value";

type DeferredSectionKey = "activity" | "quests" | "kingdoms" | "analytics";

export default class CharacterProfileShell extends React.Component<CharacterProfileShellProps> {
    state = { show_additional_character_data: false };

    showAdditionalCharacterData() {
        this.setState({
            show_additional_character_data:
                !this.state.show_additional_character_data,
        });
    }

    renderDeferredSection(
        key: DeferredSectionKey,
        loadingLabel: string,
        content: React.ReactNode,
    ): React.ReactNode {
        const error = this.props.deferred_errors[key];

        if (error !== null) {
            return <TopsErrorState message={error} />;
        }

        if (this.props.deferred_loading[key]) {
            return <TopsLoadingState label={loadingLabel} />;
        }

        return content;
    }

    render() {
        const profile = this.props.profile;
        const overview = profile.overview ?? {};
        const skills = profile.skills ?? {};
        const stats = (profile.stats ?? {}) as Record<string, TopsValue>;
        const reincarnation = (profile.reincarnation ?? {}) as Record<
            string,
            TopsValue
        >;
        const characterForAdditionalStats = {
            ...overview,
            resurrection_chance: stats.resurrection_chance ?? 0,
        } as unknown as CharacterType;

        const statDetailsValue = stats.stat_details;
        const preloadedStatDetails: StatDetailsType | null | undefined =
            typeof statDetailsValue === "undefined"
                ? undefined
                : statDetailsValue === null
                  ? null
                  : (statDetailsValue as unknown as StatDetailsType);

        const resistanceInfoValue = stats.resistance_info;
        const preloadedResistanceInfo: ResistanceInfoType | null | undefined =
            typeof resistanceInfoValue === "undefined"
                ? undefined
                : resistanceInfoValue === null
                  ? null
                  : (resistanceInfoValue as unknown as ResistanceInfoType);

        const elementalAtonementValue = stats.elemental_atonement;
        const preloadedElementalAtonement:
            | ElementalAtonementType
            | null
            | undefined =
            typeof elementalAtonementValue === "undefined"
                ? undefined
                : elementalAtonementValue === null
                  ? null
                  : (elementalAtonementValue as unknown as ElementalAtonementType);

        if (this.state.show_additional_character_data) {
            return (
                <div>
                    <div className={"max-w-[25%] my-4"}>
                        <DangerButton
                            button_label={"Close"}
                            on_click={this.showAdditionalCharacterData.bind(
                                this,
                            )}
                        />
                    </div>

                    <AdditionalStatSection
                        character={characterForAdditionalStats}
                        read_only={true}
                        preloaded_stat_details={preloadedStatDetails}
                        preloaded_resistance_info={preloadedResistanceInfo}
                        preloaded_elemental_atonement={
                            preloadedElementalAtonement
                        }
                        preloaded_reincarnation_details={
                            reincarnation.reincarnation_details as unknown as ReincarnationDetailsType
                        }
                        preloaded_class_ranks={profile.class_ranks ?? []}
                        preloaded_class_ranks_offered={
                            profile.class_ranks_offered ?? []
                        }
                        preloaded_class_rank_specialties={
                            profile.class_rank_specialties
                        }
                    />
                </div>
            );
        }

        return (
            <div className="space-y-4">
                <div className="space-y-4">
                    <div className="grid gap-4 xl:grid-cols-2">
                        <TopsCharacterInfoTabs
                            profile={profile}
                            manage_addition_data={this.showAdditionalCharacterData.bind(
                                this,
                            )}
                        />
                        <TopsCharacterSummaryCard profile={profile} />
                    </div>
                    <div className="space-y-4">
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
                <section
                    aria-label="Additional public profile details"
                    className="space-y-4"
                >
                    {this.renderDeferredSection(
                        "quests",
                        "Loading Quests",
                        <ProfileQuestsSection
                            quests={this.props.profile.quests}
                        />,
                    )}
                    {this.renderDeferredSection(
                        "kingdoms",
                        "Loading Kingdoms",
                        <ProfileKingdoms
                            kingdoms={this.props.profile.kingdoms}
                        />,
                    )}
                    {this.renderDeferredSection(
                        "analytics",
                        "Loading Analytics",
                        <ProfileAnalyticsSection
                            analytics={this.props.profile.analytics}
                        />,
                    )}
                    {this.renderDeferredSection(
                        "activity",
                        "Loading Activity",
                        <ProfileActivitySection
                            activity={this.props.profile.activity}
                        />,
                    )}
                </section>
            </div>
        );
    }
}
