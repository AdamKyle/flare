import React from "react";
import BasicCard from "../../../../../game/components/ui/cards/basic-card";
import TopsEmptyState from "../../shared/components/tops-empty-state";
import { formatTopsValue } from "../../shared/helpers/tops-format-value";
import ProfileSectionProps from "../types/profile-section-props";
import TopsValue from "../../shared/types/tops-value";
import { asTopsRecordList } from "../../shared/helpers/tops-value-helpers";

export default class ProfileSkills extends React.Component<ProfileSectionProps> {
    renderSkillList(
        title: string,
        skills: Record<string, TopsValue>[],
        levelKey: string = "level",
    ) {
        return (
            <BasicCard>
                <h2 className="text-xl font-semibold">{title}</h2>
                <div className="mt-4 grid gap-3">
                    {skills.length === 0 ? (
                        <TopsEmptyState
                            message={
                                "No " +
                                title.toLowerCase() +
                                " are available for this character."
                            }
                        />
                    ) : (
                        skills.map(
                            (skill: Record<string, TopsValue>, index: number) =>
                                this.renderSkill(title, skill, index, levelKey),
                        )
                    )}
                </div>
            </BasicCard>
        );
    }

    renderSkill(
        title: string,
        skill: Record<string, TopsValue>,
        index: number,
        levelKey: string,
    ) {
        return (
            <article
                key={String(skill.name ?? skill.class ?? title) + index}
                className="rounded-sm border border-gray-200 p-3 dark:border-gray-700"
            >
                <h3 className="font-semibold">
                    {skill.name ?? skill.class ?? "Unknown Skill"}
                </h3>
                <dl className="mt-2 grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <dt className="font-semibold">Level</dt>
                        <dd>{formatTopsValue(skill[levelKey] ?? 0)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">XP</dt>
                        <dd>
                            {formatTopsValue(skill.xp ?? skill.current_xp ?? 0)}
                            {skill.xp_max || skill.required_xp
                                ? " / " +
                                  formatTopsValue(
                                      skill.xp_max ?? skill.required_xp,
                                  )
                                : ""}
                        </dd>
                    </div>
                </dl>
            </article>
        );
    }

    renderSpecial(special: Record<string, TopsValue>, index: number) {
        return (
            <div
                key={String(special.name ?? "special") + index}
                className="rounded-sm border border-gray-200 p-3 dark:border-gray-700"
            >
                {special.name ?? "Unknown Special"}
            </div>
        );
    }

    render() {
        const skills = this.props.skills ?? {};
        const regularSkills = asTopsRecordList(skills.regular_skills).filter(
            (skill: Record<string, TopsValue>) =>
                skill.skill_type !== "crafting",
        );
        const craftingSkills = asTopsRecordList(skills.regular_skills).filter(
            (skill: Record<string, TopsValue>) =>
                skill.skill_type === "crafting",
        );
        const classSpecials = asTopsRecordList(
            skills.class_specialties_equipped,
        );

        return (
            <section className="grid gap-4 lg:grid-cols-2" aria-label="Skills">
                {this.renderSkillList("Regular Skills", regularSkills)}
                {this.renderSkillList("Crafting Skills", craftingSkills)}
                {this.renderSkillList(
                    "Passive Skills",
                    asTopsRecordList(skills.passive_skills),
                    "current_level",
                )}
                {this.renderSkillList(
                    "Class Ranks",
                    asTopsRecordList(skills.class_ranks),
                )}
                <BasicCard additionalClasses="lg:col-span-2">
                    <h2 className="text-xl font-semibold">
                        Class Specials Active
                    </h2>
                    <div className="mt-4 grid gap-2">
                        {classSpecials.length === 0 ? (
                            <TopsEmptyState message="No class specials are equipped." />
                        ) : (
                            classSpecials.map(
                                (
                                    special: Record<string, TopsValue>,
                                    index: number,
                                ) => this.renderSpecial(special, index),
                            )
                        )}
                    </div>
                </BasicCard>
            </section>
        );
    }
}
