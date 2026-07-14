import React from "react";
import BasicCard from "../../../../../../../game/components/ui/cards/basic-card";
import CharacterSkillsTabs from "../../../../../../../game/sections/character-sheet/components/character-skills-tabs";
import TopsCharacterSkillsProps from "../../../types/profile/skills/tops-character-skills-props";

export default class TopsCharacterSkills extends React.Component<TopsCharacterSkillsProps> {
    render() {
        const profile = this.props.profile ?? {};
        const skills = profile.skills ?? {};

        return (
            <BasicCard>
                <CharacterSkillsTabs
                    read_only={true}
                    finished_loading={true}
                    character_id={profile.overview?.id ?? 0}
                    user_id={0}
                    is_dead={false}
                    is_automation_running={false}
                    is_faction_loyalty_automation_running={false}
                    is_delve_running={false}
                    active_automation={null}
                    preloaded_skills={{
                        training_skills: skills.regular_skills ?? [],
                        crafting_skills: profile.crafting_skills ?? [],
                    }}
                    preloaded_kingdom_passives={profile.kingdom_passives ?? []}
                />
            </BasicCard>
        );
    }
}
