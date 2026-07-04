import React from "react";
import BasicCard from "../../../../../../../game/components/ui/cards/basic-card";
import Tabs from "../../../../../../../game/components/ui/tabs/tabs";
import TabPanel from "../../../../../../../game/components/ui/tabs/tab-panel";
import { formatNumber } from "../../../../../../../game/lib/game/format-number";

const SKILLS_TABS = [
    { key: "skills", name: "Skills" },
    { key: "crafting", name: "Crafting Skills" },
    { key: "kingdom", name: "Kingdom Passives" },
];

function SkillRows({ rows }: { rows: any[] }) {
    if (!rows || rows.length === 0) {
        return (
            <p className="text-sm text-gray-700 dark:text-gray-300">
                No public progress is available.
            </p>
        );
    }

    return (
        <div className="grid gap-3">
            {rows.map((skill: any, index: number) => (
                <article
                    key={`${skill.name ?? skill.class_name ?? index}-${index}`}
                    className="border-b border-gray-200 pb-3 last:border-b-0 dark:border-gray-700"
                >
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <h4 className="font-semibold">
                                {skill.name ?? skill.class_name ?? skill.class}
                            </h4>
                            {skill.skill_type ? (
                                <p className="text-xs uppercase text-gray-500 dark:text-gray-400">
                                    {skill.skill_type}
                                </p>
                            ) : null}
                        </div>
                        <p className="text-sm font-semibold text-gray-900 dark:text-gray-100">
                            Level {skill.level ?? skill.current_level ?? 0}
                        </p>
                    </div>
                    {"xp" in skill || "current_xp" in skill ? (
                        <div className="mt-2">
                            <div className="mb-1 flex justify-between text-xs text-orange-700 dark:text-white">
                                <span>XP</span>
                                <span>
                                    {formatNumber(
                                        skill.xp ?? skill.current_xp ?? 0,
                                    )}{" "}
                                    /{" "}
                                    {formatNumber(
                                        skill.xp_max ?? skill.required_xp ?? 0,
                                    )}
                                </span>
                            </div>
                            <div className="h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                <div
                                    className="h-1.5 rounded-full bg-orange-600"
                                    style={{
                                        width: `${Math.min(
                                            100,
                                            Math.floor(
                                                ((skill.xp ??
                                                    skill.current_xp ??
                                                    0) /
                                                    Math.max(
                                                        1,
                                                        skill.xp_max ??
                                                            skill.required_xp ??
                                                            1,
                                                    )) *
                                                    100,
                                            ),
                                        )}%`,
                                    }}
                                />
                            </div>
                        </div>
                    ) : null}
                    {skill.description ? (
                        <p className="mt-2 text-sm text-gray-700 dark:text-gray-300">
                            {skill.description}
                        </p>
                    ) : null}
                </article>
            ))}
        </div>
    );
}

export default function TopsCharacterSkillsTabs({ profile }: { profile: any }) {
    const skills = profile.skills ?? {};

    return (
        <BasicCard>
            <Tabs tabs={SKILLS_TABS} full_width={true}>
                <TabPanel key="skills">
                    <SkillRows rows={skills.regular_skills ?? []} />
                </TabPanel>
                <TabPanel key="crafting">
                    <SkillRows rows={skills.crafting_skills ?? []} />
                </TabPanel>
                <TabPanel key="kingdom">
                    <SkillRows rows={skills.kingdom_passives ?? []} />
                </TabPanel>
            </Tabs>
        </BasicCard>
    );
}
