import React, { useState } from "react";
import BasicCard from "../../../../../../../game/components/ui/cards/basic-card";
import {
    formatNumber,
    percent,
} from "../../../../../../../game/lib/game/format-number";
import ProfileClassDetailModal from "../skills/profile-class-detail-modal";
import ProfileSpecialtyDetailModal from "../skills/profile-specialty-detail-modal";
import TopsValue from "../../../../../shared/types/tops-value";

const STAT_LABELS: Record<string, string> = {
    str: "Strength",
    dur: "Durability",
    dex: "Dexterity",
    chr: "Charisma",
    int: "Intelligence",
    agi: "Agility",
    focus: "Focus",
    ac: "Armour Class",
};

const STAT_DESCRIPTIONS: Record<string, string> = {
    str: "Physical power used in melee damage calculations.",
    dur: "Toughness and base health pool.",
    dex: "Agility and evasion; often used as a to-hit stat.",
    chr: "Social influence and certain spell effects.",
    int: "Mental acuity for spell damage and skill bonuses.",
    agi: "Speed and dodge chance in combat.",
    focus: "Concentration affecting certain special abilities.",
    ac: "Total armour class; reduces incoming damage.",
};

function Divider() {
    return (
        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
    );
}

function StatDetailModal({
    statKey,
    base,
    modded,
    breakdown,
    onClose,
}: {
    statKey: string;
    base: number;
    modded: number;
    breakdown: any;
    onClose: () => void;
}) {
    React.useEffect(() => {
        const handler = (event: KeyboardEvent) => {
            if (event.key === "Escape") {
                onClose();
            }
        };

        document.addEventListener("keydown", handler);

        return () => document.removeEventListener("keydown", handler);
    }, [onClose]);

    const label = STAT_LABELS[statKey] ?? statKey.toUpperCase();

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="tops-stat-detail-title"
        >
            <div className="w-full max-w-lg rounded-sm bg-white p-6 shadow-lg dark:bg-gray-800 dark:text-gray-100">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <p className="text-sm font-semibold uppercase text-gray-600 dark:text-gray-400">
                            Character Stat
                        </p>
                        <h2
                            id="tops-stat-detail-title"
                            className="text-2xl font-semibold"
                        >
                            {label}
                        </h2>
                    </div>
                    <button
                        type="button"
                        className="rounded-sm border border-gray-300 px-3 py-1 text-sm font-semibold dark:border-gray-600"
                        onClick={onClose}
                    >
                        Close
                    </button>
                </div>
                <p className="mt-3 text-sm text-gray-700 dark:text-gray-300">
                    {STAT_DESCRIPTIONS[statKey] ?? "Public stat detail."}
                </p>
                <dl className="mt-5 grid grid-cols-2 gap-4">
                    <div>
                        <dt className="text-sm font-semibold text-gray-600 dark:text-gray-400">
                            Base
                        </dt>
                        <dd className="text-lg font-semibold">
                            {formatNumber(base)}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm font-semibold text-gray-600 dark:text-gray-400">
                            Modded
                        </dt>
                        <dd className="text-lg font-semibold">
                            {formatNumber(modded)}
                        </dd>
                    </div>
                </dl>
                {breakdown ? (
                    <dl className="mt-5">
                        <dt className="font-semibold">
                            {breakdown.label ?? label}
                        </dt>
                        <dd>{formatNumber(breakdown.value ?? modded)}</dd>
                        {breakdown.description ? (
                            <>
                                <Divider />
                                <dt className="font-semibold">Description</dt>
                                <dd>{breakdown.description}</dd>
                            </>
                        ) : null}
                    </dl>
                ) : null}
            </div>
        </div>
    );
}

function CharacterStatsCard({ stats }: { stats: any }) {
    const [selectedStat, setSelectedStat] = useState<string | null>(null);
    const base = stats.base_stats ?? {};
    const modded = stats.modded_stats ?? {};
    const breakdown = stats.stat_breakdown ?? {};
    const statKeys = ["str", "dur", "dex", "chr", "int", "agi", "focus", "ac"];

    return (
        <BasicCard>
            <h3>Character Stats</h3>
            <Divider />
            <div className="grid gap-1">
                {statKeys.map((key) => (
                    <button
                        key={key}
                        type="button"
                        className="grid w-full grid-cols-[80px_minmax(0,1fr)] items-center gap-3 py-2 text-left text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        onClick={() => setSelectedStat(key)}
                    >
                        <span className="font-semibold uppercase text-gray-700 dark:text-gray-300">
                            {key.toUpperCase()}
                        </span>
                        <span className="text-green-700 dark:text-green-400">
                            {formatNumber(base[key] ?? 0)} /{" "}
                            {formatNumber(modded[key] ?? 0)}
                        </span>
                    </button>
                ))}
            </div>
            {selectedStat !== null ? (
                <StatDetailModal
                    statKey={selectedStat}
                    base={base[selectedStat] ?? 0}
                    modded={modded[selectedStat] ?? 0}
                    breakdown={breakdown[selectedStat]}
                    onClose={() => setSelectedStat(null)}
                />
            ) : null}
        </BasicCard>
    );
}

function ClassRanksCard({ classRanks }: { classRanks: any[] }) {
    const [selectedClass, setSelectedClass] = useState<Record<
        string,
        TopsValue
    > | null>(null);
    const [selectedSpecialty, setSelectedSpecialty] = useState<Record<
        string,
        TopsValue
    > | null>(null);

    return (
        <BasicCard>
            <h3>Class Ranks</h3>
            <Divider />
            {!classRanks || classRanks.length === 0 ? (
                <p className="text-sm text-gray-700 dark:text-gray-300">
                    No public class ranks above level 1 are available.
                </p>
            ) : (
                <div className="grid gap-3">
                    {classRanks.map((rank: any, index: number) => (
                        <button
                            key={`${rank.class ?? rank.class_name ?? index}-${index}`}
                            type="button"
                            className="w-full border-b border-gray-200 pb-3 text-left last:border-b-0 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700"
                            onClick={() =>
                                setSelectedClass(
                                    rank as Record<string, TopsValue>,
                                )
                            }
                        >
                            <span className="flex items-start justify-between gap-3">
                                <span>
                                    <span className="block font-semibold">
                                        {rank.class ??
                                            rank.class_name ??
                                            "Unknown Class"}
                                    </span>
                                    <span className="block text-sm text-gray-600 dark:text-gray-400">
                                        Level {rank.level ?? 0} · XP{" "}
                                        {formatNumber(rank.current_xp ?? 0)} /{" "}
                                        {formatNumber(rank.required_xp ?? 0)}
                                    </span>
                                </span>
                                {rank.is_active ? (
                                    <span className="rounded-sm bg-green-100 px-2 py-1 text-xs font-semibold text-green-800 dark:bg-green-900 dark:text-green-100">
                                        Active
                                    </span>
                                ) : null}
                            </span>
                        </button>
                    ))}
                </div>
            )}
            {selectedClass !== null ? (
                <ProfileClassDetailModal
                    classRank={selectedClass}
                    onClose={() => setSelectedClass(null)}
                    onSelectSpecialty={(specialty) =>
                        setSelectedSpecialty(specialty)
                    }
                />
            ) : null}
            {selectedSpecialty !== null ? (
                <ProfileSpecialtyDetailModal
                    specialty={selectedSpecialty}
                    onClose={() => setSelectedSpecialty(null)}
                />
            ) : null}
        </BasicCard>
    );
}

function MeaningfulRows({
    rows,
}: {
    rows: Array<{ label: string; value: any }>;
}) {
    const meaningfulRows = rows.filter((row) => Number(row.value ?? 0) > 0);

    if (meaningfulRows.length === 0) {
        return (
            <p className="text-sm text-gray-700 dark:text-gray-300">
                No public values are available.
            </p>
        );
    }

    return (
        <dl>
            {meaningfulRows.map((row, index) => (
                <React.Fragment key={row.label}>
                    {index > 0 ? <Divider /> : null}
                    <dt className="font-semibold">{row.label}</dt>
                    <dd className="text-green-700 dark:text-green-400">
                        {percent(row.value)}%
                    </dd>
                </React.Fragment>
            ))}
        </dl>
    );
}

function ResistancesAndAtonementCard({
    resistances,
    elemental,
}: {
    resistances: any;
    elemental: any;
}) {
    const resistanceRows = [
        { label: "Spell Evasion", value: resistances?.spell_evasion },
        { label: "Artifact Annulment", value: resistances?.artifact_annulment },
        { label: "Ambush Resistance", value: resistances?.ambush_resistance },
        { label: "Counter Resistance", value: resistances?.counter_resistance },
        {
            label: "Devouring Light Resistance",
            value: resistances?.devouring_light_resistance,
        },
        {
            label: "Devouring Darkness Resistance",
            value: resistances?.devouring_darkness_resistance,
        },
    ];
    const elementalRows = Object.entries(elemental ?? {}).map(
        ([key, value]) => ({
            label: key
                .replace(/_/g, " ")
                .replace(/\b\w/g, (character) => character.toUpperCase()),
            value,
        }),
    );

    return (
        <BasicCard>
            <div className="grid gap-4 md:grid-cols-2">
                <div>
                    <h3>Resistances</h3>
                    <Divider />
                    <MeaningfulRows rows={resistanceRows} />
                </div>
                <div>
                    <h3>Elemental Atonement</h3>
                    <Divider />
                    <MeaningfulRows rows={elementalRows} />
                </div>
            </div>
        </BasicCard>
    );
}

function ReincarnationCard({ reincarnation }: { reincarnation: any }) {
    const rows = [
        {
            label: "Times Reincarnated",
            value: reincarnation?.times_reincarnated,
            percentage: false,
        },
        {
            label: "Stat Increase",
            value: reincarnation?.reincarnated_stat_increase,
            percentage: true,
        },
        {
            label: "XP Penalty",
            value: reincarnation?.xp_penalty,
            percentage: true,
        },
        {
            label: "Base Stat Mod",
            value: reincarnation?.base_stat_mod,
            percentage: true,
        },
        {
            label: "Base Damage Stat Mod",
            value: reincarnation?.base_damage_stat_mod,
            percentage: true,
        },
    ].filter((row) => Number(row.value ?? 0) > 0);

    return (
        <BasicCard>
            <h3>Reincarnation</h3>
            <Divider />
            {rows.length === 0 ? (
                <p className="text-sm text-gray-700 dark:text-gray-300">
                    No public reincarnation values are available.
                </p>
            ) : (
                <dl>
                    {rows.map((row, index) => (
                        <React.Fragment key={row.label}>
                            {index > 0 ? <Divider /> : null}
                            <dt className="font-semibold">{row.label}</dt>
                            <dd className="text-green-700 dark:text-green-400">
                                {row.percentage
                                    ? `${percent(row.value)}%`
                                    : formatNumber(row.value)}
                            </dd>
                        </React.Fragment>
                    ))}
                </dl>
            )}
        </BasicCard>
    );
}

export default function TopsCharacterAdditionalStats({
    profile,
}: {
    profile: any;
}) {
    const additional = profile.additional_stats ?? {};

    return (
        <div>
            <div className="grid md:grid-cols-2 gap-2">
                <CharacterStatsCard stats={additional.character_stats ?? {}} />
                <ClassRanksCard classRanks={additional.class_ranks ?? []} />
            </div>
            <div className="grid md:grid-cols-2 gap-2 mt-4">
                <ResistancesAndAtonementCard
                    resistances={additional.resistances ?? {}}
                    elemental={additional.elemental_atonement ?? {}}
                />
                <ReincarnationCard
                    reincarnation={additional.reincarnation ?? {}}
                />
            </div>
        </div>
    );
}
