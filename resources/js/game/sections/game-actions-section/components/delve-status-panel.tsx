import React, { useCallback, useEffect, useState } from "react";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import ItemNameColorationText from "../../../components/items/item-name/item-name-coloration-text";
import ItemDetailsModal from "../../../components/modals/item-details/item-details-modal";
import DelveQuestItemModal from "./delve-quest-item-modal";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import DangerButton from "../../../components/ui/buttons/danger-button";
import { updateTimers } from "../../../lib/ajax/update-timers";

type QuestItem = {
    id: number;
    name: string;
    type: string;
    drop_chance: number | null;
    monster_name: string | null;
    slot_id: number | null;
    have: boolean;
    had: boolean;
};

type RewardCheckpoint = {
    label: string;
    requirement: string;
    gold: string;
    special_item: string | null;
    reached: boolean;
};

type CurrentFoeStats = {
    str?: number;
    dur?: number;
    dex?: number;
    chr?: number;
    int?: number;
    agi?: number;
    focus?: number;
    ac?: number;
    health_range?: string | null;
    attack_range?: string | null;
    max_spell_damage?: number | null;
    healing_percentage?: number | null;
    xp?: number | null;
    max_level?: number | null;
    gold?: number | null;
};

type CurrentFoe = {
    id: number | null;
    name: string | null;
    pack_size: number;
    enemy_strength_boost: number;
    stats_available: boolean;
    stats: CurrentFoeStats;
    source: string;
    message: string | null;
};

type DelveStatus = {
    active: boolean;
    completed?: boolean;
    completed_at?: string;
    reason?: string;
    message?: string;
    started_at?: string;
    elapsed_seconds?: number;
    increase_enemy_strength?: number;
    increase_percentage?: number;
    quest_item_drop_hours_required?: number | null;
    quest_item_drop_seconds_remaining?: number | null;
    quest_item_drop_available_at?: string | null;
    quest_item_drop_available?: boolean;
    quest_items?: QuestItem[];
    reward_checkpoints?: RewardCheckpoint[];
    monster_name?: string | null;
    enemy_stats_available?: boolean;
    current_foe?: CurrentFoe;
};

interface DelveStatusPanelProps {
    character_id: number;
    user_id: number;
}

function formatDuration(seconds: number): string {
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;

    if (h > 0) {
        return `${h}h ${m}m ${s}s`;
    }
    if (m > 0) {
        return `${m}m ${s}s`;
    }
    return `${s}s`;
}

function formatNumber(value: unknown): string {
    const num = Number(value);
    return isNaN(num) ? "0" : num.toLocaleString();
}

function formatRange(value: unknown): string {
    if (typeof value !== "string" || !value.includes("-")) {
        return formatNumber(value ?? 0);
    }
    const parts = value.split("-");
    if (parts.length !== 2) {
        return String(value);
    }
    return parts.map((p) => formatNumber(p.trim())).join(" - ");
}

function formatPercent(value: unknown): string {
    const num = Number(value);
    return isNaN(num) ? "0.00%" : (num * 100).toFixed(2) + "%";
}

function FoeStatRow({
    label,
    value,
}: {
    label: string;
    value: React.ReactNode;
}): React.ReactElement | null {
    if (value === null || value === undefined || value === "") {
        return null;
    }
    return (
        <tr className="border-b border-gray-200 dark:border-gray-700">
            <td className="py-1 pr-4 font-semibold text-gray-700 dark:text-gray-300">
                {label}
            </td>
            <td className="py-1 text-gray-900 dark:text-gray-100">{value}</td>
        </tr>
    );
}

export default function DelveStatusPanel({
    character_id,
    user_id,
}: DelveStatusPanelProps) {
    const [status, setStatus] = useState<DelveStatus | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");
    const [elapsed, setElapsed] = useState(0);
    const [isCollapsed, setIsCollapsed] = useState(false);
    const [stopping, setStopping] = useState(false);
    const [dismissing, setDismissing] = useState(false);
    const [openSlotId, setOpenSlotId] = useState<number | null>(null);
    const [openItemId, setOpenItemId] = useState<number | null>(null);
    const [openItemName, setOpenItemName] = useState<string>("");

    const fetchStatus = useCallback(() => {
        new Ajax().setRoute(`delve/${character_id}/status`).doAjaxCall(
            "get",
            (response: { data: DelveStatus }) => {
                setStatus(response.data);
                setElapsed(response.data.elapsed_seconds ?? 0);
                setLoading(false);
            },
            (_error: AxiosError) => {
                setError("Could not load delve status.");
                setLoading(false);
            },
        );
    }, [character_id]);

    const stopDelve = useCallback(() => {
        setStopping(true);
        new Ajax().setRoute(`delve/${character_id}/stop`).doAjaxCall(
            "post",
            (_response: AxiosResponse) => {
                setStopping(false);
                updateTimers(character_id);
            },
            (_error: AxiosError) => {
                setStopping(false);
            },
        );
    }, [character_id]);

    const dismissDelve = useCallback(() => {
        setDismissing(true);
        new Ajax().setRoute(`delve/${character_id}/dismiss`).doAjaxCall(
            "post",
            (response: { data: DelveStatus }) => {
                setStatus(response.data);
                setElapsed(response.data.elapsed_seconds ?? 0);
                setDismissing(false);
            },
            (_error: AxiosError) => {
                setDismissing(false);
            },
        );
    }, [character_id]);

    useEffect(() => {
        fetchStatus();
    }, [fetchStatus]);

    useEffect(() => {
        const channelName = "delve-status-updated-" + user_id;
        const channel = window.Echo?.private(channelName);
        channel?.listen(".delve.status.updated", () => {
            fetchStatus();
        });
        return () => {
            window.Echo?.leave(channelName);
        };
    }, [user_id, fetchStatus]);

    useEffect(() => {
        if (!status?.active) {
            return;
        }
        const tick = window.setInterval(() => {
            setElapsed((prev) => prev + 1);
        }, 1000);
        return () => window.clearInterval(tick);
    }, [status?.active]);

    if (loading) {
        return (
            <div className="mt-3">
                <LoadingProgressBar />
            </div>
        );
    }

    if (error) {
        return (
            <p className="text-red-600 dark:text-red-400 text-sm">{error}</p>
        );
    }

    if (!status || (!status.active && !status.completed)) {
        return null;
    }

    const dropAvailable = status.quest_item_drop_available ?? false;
    const dropSecondsRemaining =
        status.quest_item_drop_seconds_remaining ?? null;
    const dropHoursRequired = status.quest_item_drop_hours_required ?? null;
    const foe = status.current_foe;

    const title = status.completed ? "Delve Ended" : "Delve In Progress";
    const reason = status.reason
        ? status.reason.replace(/_/g, " ")
        : status.completed
          ? "completed"
          : "running";

    return (
        <div className="w-full rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900 overflow-hidden">
            <button
                type="button"
                className="w-full cursor-pointer border-b border-gray-200 dark:border-gray-700 bg-gray-100 text-gray-700 hover:bg-gray-200 focus:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 dark:focus:bg-gray-600 px-4 py-3 text-left focus:outline-none focus:ring-2 focus:ring-inset focus:ring-gray-300 dark:focus:ring-gray-500"
                aria-expanded={!isCollapsed}
                onClick={() => setIsCollapsed(!isCollapsed)}
            >
                <span className="flex w-full items-center justify-between gap-3">
                    <span className="font-bold text-sm uppercase tracking-wide">
                        {title}
                    </span>
                    <span className="flex items-center gap-2">
                        <span className="rounded bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                            {formatDuration(elapsed)}
                        </span>
                        <span aria-hidden="true" className="text-xs">
                            {isCollapsed ? "▼" : "▲"}
                        </span>
                        <span className="sr-only">
                            {isCollapsed ? "Expand" : "Collapse"} delve status
                        </span>
                    </span>
                </span>
            </button>

            {!isCollapsed && (
                <div className="p-4">
                    {status.completed ? (
                        <div className="mb-4">
                            <p className="mb-1 text-sm">
                                <span className="font-semibold text-gray-700 dark:text-gray-300">
                                    Reason:{" "}
                                </span>
                                <span className="capitalize text-gray-900 dark:text-gray-100">
                                    {reason}
                                </span>
                            </p>
                            {status.message ? (
                                <p className="text-sm text-gray-700 dark:text-gray-300">
                                    {status.message}
                                </p>
                            ) : null}
                        </div>
                    ) : null}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div className="space-y-3">
                            <h4 className="text-sm font-semibold text-gray-800 dark:text-gray-200">
                                Current Foe
                            </h4>

                            {foe?.name ? (
                                <>
                                    <p className="text-sm font-medium text-gray-900 dark:text-white">
                                        {foe.name}
                                    </p>
                                    {foe.pack_size > 1 && (
                                        <p className="text-sm text-amber-700 dark:text-amber-300">
                                            You are fighting {foe.pack_size} of{" "}
                                            {foe.name}.
                                        </p>
                                    )}
                                </>
                            ) : (
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    {foe?.message ??
                                        "Waiting for Delve encounter"}
                                </p>
                            )}

                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                Enemy strength boost:{" "}
                                <span className="font-semibold text-red-600 dark:text-red-400">
                                    {status.increase_percentage ?? 0}%
                                </span>
                            </p>

                            <div className="rounded border border-gray-200 bg-gray-50 p-3 dark:border-gray-600 dark:bg-gray-800">
                                <p className="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Monster Stats
                                </p>
                                {foe?.stats_available ? (
                                    <>
                                        <table className="text-sm w-full">
                                            <tbody>
                                                <FoeStatRow
                                                    label="Strength"
                                                    value={formatNumber(
                                                        foe.stats.str,
                                                    )}
                                                />
                                                <FoeStatRow
                                                    label="Durability"
                                                    value={formatNumber(
                                                        foe.stats.dur,
                                                    )}
                                                />
                                                <FoeStatRow
                                                    label="Dexterity"
                                                    value={formatNumber(
                                                        foe.stats.dex,
                                                    )}
                                                />
                                                <FoeStatRow
                                                    label="Charisma"
                                                    value={formatNumber(
                                                        foe.stats.chr,
                                                    )}
                                                />
                                                <FoeStatRow
                                                    label="Intelligence"
                                                    value={formatNumber(
                                                        foe.stats.int,
                                                    )}
                                                />
                                                <FoeStatRow
                                                    label="Agility"
                                                    value={formatNumber(
                                                        foe.stats.agi,
                                                    )}
                                                />
                                                <FoeStatRow
                                                    label="Focus"
                                                    value={formatNumber(
                                                        foe.stats.focus,
                                                    )}
                                                />
                                                <FoeStatRow
                                                    label="Armour Class"
                                                    value={formatNumber(
                                                        foe.stats.ac,
                                                    )}
                                                />
                                                <FoeStatRow
                                                    label="Health Range"
                                                    value={
                                                        foe.stats.health_range
                                                            ? formatRange(
                                                                  foe.stats
                                                                      .health_range,
                                                              )
                                                            : null
                                                    }
                                                />
                                                <FoeStatRow
                                                    label="Attack Range"
                                                    value={
                                                        foe.stats.attack_range
                                                            ? formatRange(
                                                                  foe.stats
                                                                      .attack_range,
                                                              )
                                                            : null
                                                    }
                                                />
                                                <FoeStatRow
                                                    label="Spell Damage"
                                                    value={formatNumber(
                                                        foe.stats
                                                            .max_spell_damage,
                                                    )}
                                                />
                                                <FoeStatRow
                                                    label="Healing"
                                                    value={formatPercent(
                                                        foe.stats
                                                            .healing_percentage ??
                                                            0,
                                                    )}
                                                />
                                                <FoeStatRow
                                                    label="XP Per Kill"
                                                    value={formatNumber(
                                                        foe.stats.xp,
                                                    )}
                                                />
                                                <FoeStatRow
                                                    label="Max Level"
                                                    value={formatNumber(
                                                        foe.stats.max_level,
                                                    )}
                                                />
                                                <FoeStatRow
                                                    label="Gold Per Kill"
                                                    value={formatNumber(
                                                        foe.stats.gold,
                                                    )}
                                                />
                                            </tbody>
                                        </table>
                                        {foe.message && (
                                            <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                {foe.message}
                                            </p>
                                        )}
                                    </>
                                ) : (
                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                        {foe?.source === "active_delve"
                                            ? (foe?.message ??
                                              "Base stats not available.")
                                            : "Not available"}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="space-y-4">
                            {dropHoursRequired !== null && (
                                <div className="rounded border border-gray-200 bg-gray-50 p-3 dark:border-gray-600 dark:bg-gray-800">
                                    <p className="text-sm font-medium text-gray-800 dark:text-gray-200">
                                        Quest Item Drop
                                    </p>
                                    {dropAvailable ? (
                                        <p className="mt-1 text-xs text-green-600 dark:text-green-400">
                                            Available now
                                        </p>
                                    ) : dropSecondsRemaining !== null ? (
                                        <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            Available in:{" "}
                                            {formatDuration(
                                                Math.max(
                                                    0,
                                                    dropSecondsRemaining,
                                                ),
                                            )}{" "}
                                            (requires {dropHoursRequired}h
                                            elapsed)
                                        </p>
                                    ) : null}
                                </div>
                            )}

                            {status.quest_items &&
                                status.quest_items.length > 0 && (
                                    <div>
                                        <h4 className="mb-2 text-sm font-semibold text-gray-800 dark:text-gray-200">
                                            Quest Items
                                        </h4>
                                        <ul className="space-y-1">
                                            {status.quest_items.map((item) => (
                                                <li
                                                    key={item.id}
                                                    className="flex items-center justify-between gap-2 text-sm"
                                                >
                                                    {item.slot_id !== null ? (
                                                        <button
                                                            type="button"
                                                            className="text-left hover:underline focus:outline-none"
                                                            aria-label={`View details for ${item.name}`}
                                                            onClick={() =>
                                                                setOpenSlotId(
                                                                    item.slot_id,
                                                                )
                                                            }
                                                        >
                                                            <ItemNameColorationText
                                                                item={{
                                                                    name: item.name,
                                                                    type: item.type,
                                                                    affix_count: 0,
                                                                    is_unique:
                                                                        false,
                                                                    holy_stacks_applied: 0,
                                                                    is_mythic:
                                                                        false,
                                                                    is_cosmic:
                                                                        false,
                                                                }}
                                                                custom_width={
                                                                    false
                                                                }
                                                            />
                                                        </button>
                                                    ) : (
                                                        <button
                                                            type="button"
                                                            className="text-left hover:underline focus:outline-none"
                                                            aria-label={`View details for ${item.name}`}
                                                            onClick={() => {
                                                                setOpenItemId(
                                                                    item.id,
                                                                );
                                                                setOpenItemName(
                                                                    item.name,
                                                                );
                                                            }}
                                                        >
                                                            <ItemNameColorationText
                                                                item={{
                                                                    name: item.name,
                                                                    type: item.type,
                                                                    affix_count: 0,
                                                                    is_unique:
                                                                        false,
                                                                    holy_stacks_applied: 0,
                                                                    is_mythic:
                                                                        false,
                                                                    is_cosmic:
                                                                        false,
                                                                }}
                                                                custom_width={
                                                                    false
                                                                }
                                                            />
                                                        </button>
                                                    )}
                                                    <span className="flex gap-2 text-xs">
                                                        <span
                                                            title="Currently in inventory"
                                                            className={
                                                                item.have
                                                                    ? "text-green-600 dark:text-green-400"
                                                                    : "text-gray-400"
                                                            }
                                                        >
                                                            {item.have
                                                                ? "✓ Have"
                                                                : "✗ Have"}
                                                        </span>
                                                        <span
                                                            title="Used in a completed quest"
                                                            className={
                                                                item.had
                                                                    ? "text-blue-600 dark:text-blue-400"
                                                                    : "text-gray-400"
                                                            }
                                                        >
                                                            {item.had
                                                                ? "✓ Had"
                                                                : "✗ Had"}
                                                        </span>
                                                    </span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}

                            {status.quest_items &&
                                status.quest_items.length === 0 && (
                                    <p className="text-sm text-gray-500 dark:text-gray-400">
                                        No quest items available at this Cave of
                                        Memories location.
                                    </p>
                                )}

                            {status.reward_checkpoints && (
                                <div>
                                    <h4 className="mb-2 text-sm font-semibold text-gray-800 dark:text-gray-200">
                                        Reward Checkpoints
                                    </h4>
                                    <ul className="space-y-1">
                                        {status.reward_checkpoints.map((cp) => (
                                            <li
                                                key={cp.label}
                                                className={`flex items-start gap-2 text-sm ${cp.reached ? "text-gray-900 dark:text-white" : "text-gray-400 dark:text-gray-500"}`}
                                            >
                                                <span className="mt-0.5 shrink-0">
                                                    {cp.reached ? (
                                                        <span className="text-green-600 dark:text-green-400">
                                                            ✓
                                                        </span>
                                                    ) : (
                                                        <span>◯</span>
                                                    )}
                                                </span>
                                                <span>
                                                    <span className="font-medium">
                                                        {cp.label}
                                                    </span>
                                                    {" — "}
                                                    {cp.gold} gold
                                                    {cp.special_item
                                                        ? ` + ${cp.special_item}`
                                                        : ""}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            )}
                        </div>
                    </div>
                    {status.completed ? (
                        <div className="mt-4">
                            <DangerButton
                                button_label={"Close"}
                                on_click={dismissDelve}
                                disabled={dismissing}
                                additional_css={""}
                            />
                        </div>
                    ) : null}
                </div>
            )}

            {openSlotId !== null && (
                <ItemDetailsModal
                    is_open={true}
                    character_id={character_id}
                    slot_id={openSlotId}
                    is_automation_running={true}
                    is_dead={false}
                    manage_modal={() => setOpenSlotId(null)}
                />
            )}

            {openItemId !== null && (
                <DelveQuestItemModal
                    is_open={true}
                    item_id={openItemId}
                    item_name={openItemName}
                    character_id={character_id}
                    manage_modal={() => setOpenItemId(null)}
                />
            )}
        </div>
    );
}
