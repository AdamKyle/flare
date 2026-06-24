import React, { Fragment } from "react";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError } from "axios";
import DangerButton from "../../../components/ui/buttons/danger-button";
import { startCase } from "lodash";
import { ExplorationOutputType } from "../../../lib/game/types/game-state";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import { Transition } from "@headlessui/react";

interface ExplorationOutputSectionProps {
    character_id: number;
    exploration_output: ExplorationOutputType | null;
}

interface ExplorationOutputSectionState {
    dismissing: boolean;
    collapsed: boolean;
}

export default class ExplorationOutputSection extends React.Component<
    ExplorationOutputSectionProps,
    ExplorationOutputSectionState
> {
    constructor(props: ExplorationOutputSectionProps) {
        super(props);

        this.state = {
            dismissing: false,
            collapsed: false,
        };
    }

    toggleCollapsed(): void {
        this.setState({
            collapsed: !this.state.collapsed,
        });
    }

    dismissWarning(): void {
        const warningId = this.props.exploration_output?.output?.id;

        this.setState({ dismissing: true }, () => {
            new Ajax()
                .setRoute(
                    "exploration/" +
                        this.props.character_id +
                        "/warning/dismiss",
                )
                .setParameters(warningId ? { warning_id: warningId } : {})
                .doAjaxCall(
                    "post",
                    () => {
                        this.setState({
                            dismissing: false,
                        });
                    },
                    (_error: AxiosError) => {
                        this.setState({ dismissing: false });
                    },
                );
        });
    }

    formatReason(reason: string): string {
        return startCase(reason.replace(/_/g, " "));
    }

    formatNumber(value: any): string {
        const num = Number(value);
        return isNaN(num) ? "0" : num.toLocaleString();
    }

    formatAttackRange(value: any): string {
        if (typeof value !== "string" || !value.includes("-")) {
            return this.formatNumber(value ?? 0);
        }

        const parts = value.split("-");

        if (parts.length !== 2) {
            return value;
        }

        return parts
            .map((part: string) => this.formatNumber(part.trim()))
            .join(" - ");
    }

    formatPercent(value: any): string {
        const num = Number(value);
        return isNaN(num) ? "0.00%" : (num * 100).toFixed(2) + "%";
    }

    formatDurationCompact(seconds: number): string {
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

    formatDuration(value: any): string {
        const seconds = Number(value);

        if (isNaN(seconds) || seconds <= 0) {
            return "0 seconds";
        }

        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const remainingSeconds = seconds % 60;
        const parts: string[] = [];

        if (hours > 0) {
            parts.push(hours + " hour" + (hours === 1 ? "" : "s"));
        }

        if (minutes > 0) {
            parts.push(minutes + " minute" + (minutes === 1 ? "" : "s"));
        }

        if (remainingSeconds > 0 || parts.length === 0) {
            parts.push(
                remainingSeconds +
                    " second" +
                    (remainingSeconds === 1 ? "" : "s"),
            );
        }

        return parts.join(", ");
    }

    renderRow(label: string, value: React.ReactNode): React.ReactNode {
        if (value === null || typeof value === "undefined" || value === "") {
            return null;
        }

        return (
            <tr
                key={label}
                className="border-b border-gray-200 dark:border-gray-700"
            >
                <td className="py-1 pr-4 font-semibold text-gray-700 dark:text-gray-300">
                    {label}
                </td>
                <td className="py-1 text-gray-900 dark:text-gray-100">
                    {value}
                </td>
            </tr>
        );
    }

    renderNumberRow(label: string, value: any): React.ReactNode {
        return this.renderRow(label, this.formatNumber(value ?? 0));
    }

    renderMonsterStats(data: Record<string, any>): React.ReactNode {
        const monster = data.monster ?? {};
        const stats = monster.stats ?? {};
        const hasAttackDamage = Object.prototype.hasOwnProperty.call(
            stats,
            "attack_damage",
        );
        const hasHealth = Object.prototype.hasOwnProperty.call(stats, "health");
        const healthIsRange =
            typeof stats.health_range === "string" &&
            stats.health_range.includes("-");
        const healthLabel = hasHealth
            ? "Health"
            : healthIsRange
              ? "Health Range"
              : "Health";
        const attackLabel = hasAttackDamage ? "Attack" : "Attack Range";
        const attackValue = hasAttackDamage
            ? this.formatNumber(stats.attack_damage ?? 0)
            : this.formatAttackRange(stats.attack_range);

        return (
            <table className="text-sm w-full">
                <tbody>
                    {this.renderNumberRow("Strength", stats.str)}
                    {this.renderNumberRow("Durability", stats.dur)}
                    {this.renderNumberRow("Dexterity", stats.dex)}
                    {this.renderNumberRow("Charisma", stats.chr)}
                    {this.renderNumberRow("Intelligence", stats.int)}
                    {this.renderNumberRow("Agility", stats.agi)}
                    {this.renderNumberRow("Focus", stats.focus)}
                    {this.renderNumberRow("Armour Class", stats.ac)}
                    {hasHealth
                        ? this.renderNumberRow(healthLabel, stats.health)
                        : healthIsRange
                          ? this.renderRow(healthLabel, stats.health_range)
                          : this.renderNumberRow(
                                healthLabel,
                                stats.health_range,
                            )}
                    {this.renderRow(attackLabel, attackValue)}
                    {this.renderNumberRow(
                        "Spell Damage",
                        stats.max_spell_damage,
                    )}
                    {this.renderRow(
                        "Healing",
                        this.formatPercent(stats.healing_percentage ?? 0),
                    )}
                    {this.renderNumberRow("XP Per Kill", stats.xp)}
                    {this.renderNumberRow("Max Level", stats.max_level)}
                    {this.renderNumberRow("Gold Per Kill", stats.gold)}
                </tbody>
            </table>
        );
    }

    renderRewards(data: Record<string, any>): React.ReactNode {
        const totals = data.totals ?? {};
        const currencies = data.currencies ?? data.currencies_gained ?? {};
        const damage = data.damage ?? {};

        return (
            <table className="text-sm w-full">
                <tbody>
                    {this.renderNumberRow("Kills", totals.kills)}
                    {this.renderNumberRow("XP Gained", totals.xp)}
                    {this.renderNumberRow("Skill XP", totals.skill_xp)}
                    {this.renderNumberRow(
                        "Faction Points",
                        totals.faction_points,
                    )}
                    {this.renderNumberRow(
                        "Levels Gained",
                        currencies.levels_gained,
                    )}
                    {this.renderNumberRow("Weapon Damage", damage.weapon)}
                    {this.renderNumberRow("Spell Damage", damage.spell)}
                    {this.renderNumberRow("Healing Done", data.healing)}
                    {this.renderNumberRow("Damage Blocked", data.blocked)}
                    {this.renderNumberRow("Gold", currencies.gold)}
                    {this.renderNumberRow("Gold Dust", currencies.gold_dust)}
                    {this.renderNumberRow("Shards", currencies.shards)}
                    {this.renderNumberRow(
                        "Copper Coins",
                        currencies.copper_coins,
                    )}
                    {this.renderRow(
                        "Duration",
                        this.formatDuration(data.duration),
                    )}
                    {this.renderRow(
                        "Reason",
                        this.formatReason(data.reason ?? "running"),
                    )}
                </tbody>
            </table>
        );
    }

    renderMonsterLink(data: Record<string, any>): React.ReactNode {
        const monster = data.monster ?? {};
        const monsterId = monster.id ?? data.monster_id;
        const monsterLink = monster.link ?? "/monsters/" + monsterId;
        const monsterName = monster.name ?? "View Monster";

        if (!monsterId) {
            return null;
        }

        return (
            <p className="mb-3 text-sm font-semibold">
                <a
                    href={monsterLink}
                    target="_blank"
                    rel="noreferrer"
                    className="text-sky-600 dark:text-sky-300 hover:underline"
                >
                    {monsterName}{" "}
                    <i className="fas fa-external-link-alt text-xs"></i>
                </a>
            </p>
        );
    }

    renderMonsterTitle(data: Record<string, any>): React.ReactNode {
        const monster = data.monster ?? {};
        const monsterId = monster.id ?? data.monster_id;
        const monsterLink = monster.link ?? "/monsters/" + monsterId;
        const monsterName = monster.name ?? "Monster Stats";

        if (!monsterId) {
            return (
                <h4 className="font-bold text-gray-700 dark:text-gray-300 mb-2 text-sm uppercase tracking-wide">
                    {monsterName}
                </h4>
            );
        }

        return (
            <h4 className="font-bold mb-2 text-sm uppercase tracking-wide">
                <a
                    href={monsterLink}
                    target="_blank"
                    rel="noreferrer"
                    className="text-sky-600 dark:text-sky-300 hover:underline"
                >
                    {monsterName}{" "}
                    <i className="fas fa-external-link-alt text-xs"></i>
                </a>
            </h4>
        );
    }

    renderOutputColumns(data: Record<string, any>): React.ReactNode {
        const totals = data.totals ?? {};
        const fights = this.formatNumber(totals.fights ?? 0);
        const currentRoundCreatures = data.current_round_creatures;
        const currentRoundCopy =
            currentRoundCreatures === null ||
            typeof currentRoundCreatures === "undefined"
                ? "the current round"
                : this.formatNumber(currentRoundCreatures) +
                  " creatures in the current round";

        return (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="p-3">
                    {this.renderMonsterTitle(data)}
                    {this.renderMonsterStats(data)}
                    <p className="mt-3 text-xs text-gray-600 dark:text-gray-400">
                        These stats start off as the base stats until you begin
                        the exploration, then we show that monster&apos;s stats.
                        Because the monster is rebuilt every time you fight one,
                        we only show you the first of {currentRoundCopy}.
                        Clicking the monster name opens the base stats page.
                    </p>
                </div>
                <div className="p-3">
                    <h4 className="font-bold text-gray-700 dark:text-gray-300 mb-2 text-sm uppercase tracking-wide">
                        Rewards Total ({fights} encounters)
                    </h4>
                    {this.renderRewards(data)}
                </div>
            </div>
        );
    }

    renderCardHeader(
        title: string,
        contentId: string,
        color: "sky" | "orange",
        durationLabel?: string | null,
    ): React.ReactNode {
        const colorClasses =
            color === "sky"
                ? "bg-gray-100 text-gray-700 hover:bg-gray-200 focus:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 dark:focus:bg-gray-600 border-gray-200 dark:border-gray-700"
                : "bg-orange-100 text-orange-700 hover:bg-orange-200 focus:bg-orange-200 dark:bg-orange-900/60 dark:text-orange-300 dark:hover:bg-orange-900 dark:focus:bg-orange-900 border-orange-500 dark:border-orange-400";

        return (
            <button
                type="button"
                className={
                    "w-full cursor-pointer border-b px-4 py-3 text-left focus:outline-none focus:ring-2 focus:ring-inset focus:ring-gray-300 dark:focus:ring-gray-500 " +
                    colorClasses
                }
                aria-expanded={!this.state.collapsed}
                aria-controls={contentId}
                onClick={this.toggleCollapsed.bind(this)}
            >
                <span className="flex w-full items-center justify-between gap-3">
                    <span className="font-bold text-sm uppercase tracking-wide">
                        {title}
                    </span>
                    <span className="flex items-center gap-2">
                        {durationLabel ? (
                            <span className="rounded bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                {durationLabel}
                            </span>
                        ) : null}
                        <span aria-hidden="true" className="text-xs">
                            {this.state.collapsed ? "▼" : "▲"}
                        </span>
                        <span className="sr-only">
                            {this.state.collapsed
                                ? "Expand exploration output"
                                : "Collapse exploration output"}
                        </span>
                    </span>
                </span>
            </button>
        );
    }

    renderCardBody(
        contentId: string,
        children: React.ReactNode,
    ): React.ReactNode {
        return (
            <Transition
                as={Fragment}
                show={!this.state.collapsed}
                enter="transition-all duration-200 ease-out overflow-hidden"
                enterFrom="max-h-0 opacity-0"
                enterTo="max-h-[2000px] opacity-100"
                leave="transition-all duration-150 ease-in overflow-hidden"
                leaveFrom="max-h-[2000px] opacity-100"
                leaveTo="max-h-0 opacity-0"
            >
                <div id={contentId} className="p-4">
                    {children}
                </div>
            </Transition>
        );
    }

    renderActiveOutput(data?: Record<string, any>): React.ReactNode {
        if (!data) {
            return null;
        }

        const contentId = "exploration-output-active-body";
        const durationSeconds = Number(data.duration);
        const durationLabel =
            !isNaN(durationSeconds) && durationSeconds > 0
                ? this.formatDurationCompact(durationSeconds)
                : null;

        return (
            <div className="w-full rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900 mt-3 overflow-hidden">
                {this.renderCardHeader(
                    "Exploration In Progress",
                    contentId,
                    "sky",
                    durationLabel,
                )}
                {this.renderCardBody(contentId, this.renderOutputColumns(data))}
            </div>
        );
    }

    renderWarningOutput(data?: Record<string, any>): React.ReactNode {
        if (!data) {
            return null;
        }

        const contentId = "exploration-output-warning-body";

        return (
            <div className="w-full border border-orange-500 dark:border-orange-400 rounded mt-3 overflow-hidden bg-white dark:bg-gray-800">
                {this.renderCardHeader(
                    "Exploration Ended",
                    contentId,
                    "orange",
                )}
                {this.renderCardBody(
                    contentId,
                    <>
                        <p className="mb-1 text-sm">
                            <span className="font-semibold text-gray-700 dark:text-gray-300">
                                Reason:{" "}
                            </span>
                            <span className="text-gray-900 dark:text-gray-100">
                                {this.formatReason(
                                    data.reason ?? data.type ?? "unknown",
                                )}
                            </span>
                        </p>
                        {data.message ? (
                            <p className="mb-3 text-sm text-gray-700 dark:text-gray-300">
                                {data.message}
                            </p>
                        ) : null}
                        {this.renderOutputColumns(data)}
                        <DangerButton
                            button_label={"Dismiss"}
                            on_click={this.dismissWarning.bind(this)}
                            disabled={this.state.dismissing}
                            additional_css={""}
                        />
                    </>,
                )}
            </div>
        );
    }

    render() {
        const explorationOutput = this.props.exploration_output;
        const data = explorationOutput?.output;

        if (explorationOutput?.loading && !data) {
            return (
                <div className="mt-3">
                    <LoadingProgressBar />
                </div>
            );
        }

        if (explorationOutput?.type === "active" && data !== null) {
            return this.renderActiveOutput(data);
        }

        if (explorationOutput?.type === "warning" && data !== null) {
            return this.renderWarningOutput(data);
        }

        return null;
    }
}
