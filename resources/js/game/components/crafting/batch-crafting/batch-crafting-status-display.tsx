import React from "react";
import { startCase } from "lodash";
import { AxisOptions, Chart } from "react-charts";
import DangerButton from "../../ui/buttons/danger-button";
import PrimaryButton from "../../ui/buttons/primary-button";
import ItemNameColorationText from "../../items/item-name/item-name-coloration-text";
import InventoryUseDetails from "../../../sections/character-sheet/components/modals/inventory-item-details";
import ItemAffixDetails from "../../../sections/character-sheet/components/modals/components/item-affix-details";
import ItemDetails from "../../../sections/character-sheet/components/modals/components/item-details";
import Dialogue from "../../ui/dialogue/dialogue";
import InfoAlert from "../../ui/alerts/simple-alerts/info-alert";
import WarningAlert from "../../ui/alerts/simple-alerts/warning-alert";
import BatchCraftingStatusDisplayProps from "./types/batch-crafting-status-display-props";
import BatchCraftingStatusDisplayState from "./types/batch-crafting-status-display-state";
import { formatLocalDateTime } from "../../../lib/game/format-local-date";
import { formatNumber } from "../../../lib/game/format-number";

type ChartPoint = { label: string; value: number };
type ChartLine = { label: string; data: ChartPoint[]; color?: string };

function BatchLineChart({
    title,
    lines,
    yAxisLabel,
}: {
    title: string;
    lines: ChartLine[];
    yAxisLabel: string;
}) {
    const hasData = lines.some((line) => line.data.length > 0);
    const displayLines = React.useMemo(
        (): ChartLine[] =>
            lines.map((line) => {
                if (line.data.length !== 1) {
                    return line;
                }

                const [onlyPoint] = line.data;
                const priorTick = Math.max(0, Number(onlyPoint.label) - 1);

                return {
                    ...line,
                    data: [
                        { label: String(priorTick), value: onlyPoint.value },
                        onlyPoint,
                    ],
                };
            }),
        [lines],
    );
    const primaryAxis = React.useMemo(
        (): AxisOptions<ChartPoint> => ({
            getValue: (datum) => datum.label,
        }),
        [],
    );
    const secondaryAxes = React.useMemo(
        (): AxisOptions<ChartPoint>[] => [
            {
                getValue: (datum) => datum.value,
                elementType: "line",
            },
        ],
        [],
    );
    const chartId = title.toLowerCase().replace(/[^a-z0-9]+/g, "-");
    const summary = lines
        .map((line) => {
            const last = line.data[line.data.length - 1];

            return `${line.label} is currently ${formatNumber(last?.value ?? 0)}`;
        })
        .join(". ");
    const hasLineColors = lines.some((line) => line.color);
    const getSeriesStyle = React.useCallback(
        (series: { index: number }) => {
            const color = displayLines[series.index]?.color;

            return color ? { fill: color, stroke: color } : {};
        },
        [displayLines],
    );

    return (
        <div>
            <h4 id={chartId} className="mb-1 font-semibold">
                {title}
            </h4>
            <p id={`${chartId}-summary`} className="sr-only">
                {summary || `No data recorded yet for ${title}.`}
            </p>
            {hasData ? (
                <>
                    <div className="mb-1 flex justify-between text-xs uppercase text-gray-500 dark:text-gray-400">
                        <span>Tick</span>
                        <span>{yAxisLabel}</span>
                    </div>
                    <div
                        className="h-48 w-full overflow-hidden rounded-sm border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
                        role="img"
                        aria-labelledby={chartId}
                        aria-describedby={`${chartId}-summary`}
                    >
                        <Chart
                            options={{
                                data: displayLines,
                                primaryAxis,
                                secondaryAxes,
                                dark: true,
                                ...(hasLineColors
                                    ? { getSeriesStyle: getSeriesStyle }
                                    : {}),
                            }}
                        />
                    </div>
                </>
            ) : (
                <p className="text-sm text-gray-600 dark:text-gray-400">
                    No chart data has been recorded yet.
                </p>
            )}
        </div>
    );
}

export type BatchCraftingItemSnapshot = {
    slot_id?: number | null;
    item_id?: number | null;
    item_id_for_modal?: number | null;
    slot_id_for_modal?: number | null;
    set_slot_id?: number | null;
    name?: string;
    type?: string;
    affix_count?: number;
    is_unique?: boolean;
    holy_stacks_applied?: number;
    is_mythic?: boolean;
    is_cosmic?: boolean;
    can_view?: boolean;
    status?: string;
    description?: string | null;
    base_damage?: number;
    base_ac?: number;
    base_healing?: number;
    str_modifier?: number;
    dex_modifier?: number;
    agi_modifier?: number;
    chr_modifier?: number;
    dur_modifier?: number;
    int_modifier?: number;
    focus_modifier?: number;
    full_item_details?: any | null;
};

export type BatchCraftingActionLogEntry = {
    ts: string;
    timestamp?: string;
    action_type?: string;
    status?: string;
    disposition?: string;
    crafted_item?: BatchCraftingItemSnapshot | null;
    enchanted_item?: BatchCraftingItemSnapshot | null;
    alchemy_item?: BatchCraftingItemSnapshot | null;
    trinketry_item?: BatchCraftingItemSnapshot | null;
    kept_item?: BatchCraftingItemSnapshot | null;
    sold_item?: BatchCraftingItemSnapshot | null;
    destroyed_item?: BatchCraftingItemSnapshot | null;
    listed_item?: BatchCraftingItemSnapshot | null;
    disenchanted_item?: BatchCraftingItemSnapshot | null;
    gold_gained?: number;
    gold_dust_gained?: number;
    gold_spent?: number;
    listed_price?: number;
    failure?: string;
    phase?: string;
    prefix_affix_name?: string | null;
    suffix_affix_name?: string | null;
    prefix_affix?: any | null;
    suffix_affix?: any | null;
    prefix_applied?: boolean;
    suffix_applied?: boolean;
    destination_set?: string;
    created_in_crafted_items_set?: boolean;
    oil_application?: {
        target_item?: BatchCraftingItemSnapshot | null;
        oil_item?: BatchCraftingItemSnapshot | null;
        item_id?: number;
        oil_slot_id?: number;
    };
};

export type BatchCraftingSkillData = {
    key: string;
    name: string;
    level: number;
    max_level: number;
    current_xp: number;
    next_level_xp: number;
    xp_percent: number;
    is_maxed: boolean;
};

export type BatchCraftingExperienceOption = {
    value: string;
    label: string;
    batch_type: string | null;
    skill_id: number | null;
    skill_name: string;
    current_level: number;
    max_level: number;
    current_xp: number;
    required_xp: number;
    progress_percent: number;
    is_maxed: boolean;
    is_available: boolean;
};

export type BatchCraftingStatus = {
    active: boolean;
    is_running?: boolean;
    is_visible?: boolean;
    can_cancel?: boolean;
    can_dismiss?: boolean;
    completed: boolean;
    status?: string;
    show_info: boolean;
    craft_mode_availability?: {
        can_craft_for_experience: boolean;
        can_craft_and_enchant_for_experience: boolean;
    };
    craft_experience_options?: BatchCraftingExperienceOption[];
    event_batch?: {
        can_craft_for_event: boolean;
        can_enchant_for_event: boolean;
        event_type: number | null;
        event_name: string | null;
        current_step: string | null;
        goal_remaining: number | null;
        actions_per_tick: number;
        tick_rate_seconds: number;
        max_runtime_hours: number;
        active_event_mode: boolean;
        crafting_skills_maxed: boolean;
        alchemy_locked: boolean;
        alchemy_maxed: boolean;
        trinketry_maxed: boolean;
        enchanting_maxed: boolean;
    };
    batch?: {
        id: number;
        batch_label: string;
        batch_type: string;
        type?: string;
        human_mode_label?: string;
        disposition: string;
        started_at: string | null;
        ends_at: string | null;
        ended_at?: string | null;
        completed_at: string | null;
        elapsed_seconds?: number;
        remaining_seconds?: number;
        elapsed_human?: string;
        remaining_human?: string;
        progress_percent?: number;
        event_crafting_xp_gained?: number;
        event_enchanting_xp_gained?: number;
        requested_amount?: number | null;
        completed_amount?: number | null;
        remaining_amount?: number | null;
        completion_summary?: "all" | "some" | "none" | null;
        current_item_name?: string | null;
        current_item_snapshot?: BatchCraftingItemSnapshot | null;
        current_crafted_item_snapshot?: BatchCraftingItemSnapshot | null;
        current_enchanted_item_snapshot?: BatchCraftingItemSnapshot | null;
        alchemy_current_item?: BatchCraftingItemSnapshot | null;
        trinketry_current_item?: BatchCraftingItemSnapshot | null;
        crafted_item_snapshots?: unknown[];
        enchanted_item_snapshots?: unknown[];
        alchemy_item_snapshots?: unknown[];
        trinketry_item_snapshots?: unknown[];
        holy_oil_target_item_snapshots?: unknown[];
        gold_spent?: number;
        gold_spent_total?: number;
        gold_gained_total?: number;
        gold_left?: number;
        gold_dust_spent_total?: number;
        gold_dust_gained_total?: number;
        gold_dust_left?: number;
        shards_spent_total?: number;
        shards_gained_total?: number;
        shards_left?: number;
        listing_price_per_item?: number | null;
        total_listed_value?: number;
        potential_seller_net?: number;
        craft_set_current_item?: BatchCraftingItemSnapshot | null;
        craft_enchant_set_phase?:
            | "crafting"
            | "enchanting"
            | "replacement_crafting"
            | null;
        craft_enchant_set_requested?: number | null;
        craft_enchant_set_prefix_applied_count?: number | null;
        craft_enchant_set_suffix_applied_count?: number | null;
        craft_enchant_set_completed_final_count?: number | null;
        craft_enchant_set_craft_completed_count?: number | null;
        craft_enchant_set_enchant_completed_count?: number | null;
        craft_enchant_set_total_work_units?: number | null;
        craft_enchant_set_completed_work_units?: number | null;
        craft_enchant_set_remaining_work_units?: number | null;
        craft_enchant_set_overall_percent?: number | null;
        craft_enchant_set_current_item?: BatchCraftingItemSnapshot | null;
        craft_enchant_set_current_prefix?: string | null;
        craft_enchant_set_current_suffix?: string | null;
        craft_enchant_set_current_prefix_affix?: any | null;
        craft_enchant_set_current_suffix_affix?: any | null;
        enchant_set_total?: number | null;
        enchant_set_completed?: number | null;
        enchant_set_skipped?: number | null;
        enchant_set_current_item?: BatchCraftingItemSnapshot | null;
        enchant_affix_ids?: number[] | null;
        enchant_affix_names?: string[];
        enchant_affixes?: any[] | null;
        holy_oil_eligible_items?: number | null;
        holy_oil_total_stacks?: number | null;
        holy_oil_requested_applications?: number | null;
        holy_oil_completed_applications?: number | null;
        holy_oil_remaining_applications?: number | null;
        holy_oil_total_stat_bonus_applied?: number;
        holy_oil_total_devouring_darkness_bonus_applied?: number;
        holy_oil_gold_dust_spent?: number;
        holy_oil_skipped_items?: number;
        holy_oil_selected_item_count?: number;
        holy_oil_selected_oil_count?: number;
        holy_oil_current_target_item?: BatchCraftingItemSnapshot | null;
        holy_oil_current_oil_item?: BatchCraftingItemSnapshot | null;
        ended_reason: string | null;
        stop_reason?: string | null;
        status?: string;
        event_mode?: boolean;
        event_action?: string | null;
        event_type?: number | null;
        event_step?: string | null;
        event_actions_per_tick?: number | null;
        actions_per_minute?: number | null;
        experience_rate_label?: string | null;
        next_action?: string | null;
        event_goal_progress?: {
            current: number;
            max: number;
        } | null;
        event_character_contribution?: {
            current: number;
            reward_threshold: number;
        } | null;
        event_enchant_phase?: string | null;
        event_fallback_phase?: string | null;
        event_current_phase_label?: string | null;
        event_stop_reason?: string | null;
        event_fallback_crafted_this_tick?: number;
        event_fallback_enchanted_this_tick?: number;
        mode?: string | null;
        phase?: string | null;
        last_action?: string | null;
        selected_set?: {
            id: number;
            name: string;
            current_slots: number;
            max_slots: number | null;
            remaining_slots: number;
        } | null;
        output_destination?: string | null;
        output_destination_label?: string | null;
        output_set?: {
            id: number;
            name: string;
            current_slots: number;
            max_slots: number | null;
            remaining_slots: number;
        } | null;
        chart_points?: {
            currency: {
                tick: number;
                gold_spent: number;
                gold_gained: number;
                gold_dust_spent: number;
                gold_dust_gained: number;
                shards_spent: number;
                shards_gained: number;
                listed_value: number;
            }[];
            outcomes: { tick: number; success: number; failure: number }[];
            gold_dust: { tick: number; gained: number }[];
        };
        inventory_count: number;
        inventory_max: number;
        inventory_percent?: number;
        alchemy_bag_count: number;
        alchemy_bag_max: number;
        alchemy_bag_remaining: number;
        batch_crafting_set?: {
            current_slots: number;
            max_slots: number;
            remaining_slots: number;
            percent?: number;
        };
        skills?: BatchCraftingSkillData[];
        skills_being_trained?: BatchCraftingExperienceOption[];
        currency: {
            type: string;
            amount: number;
        };
        counts: {
            crafted: number;
            sold: number;
            destroyed: number;
            listed: number;
            disenchanted?: number;
            enchanted?: number;
            alchemy_processed?: number;
            trinketry_processed?: number;
            kept: number;
            applied: number;
            skipped: number;
            failed: number;
        };
        kept_set_summary?: {
            message: string;
            items: BatchCraftingItemSnapshot[];
        } | null;
        amount_preview?: {
            selected_item: BatchCraftingItemSnapshot | null;
            requested_amount: number;
            completed_amount: number;
            remaining_requested_amount: number;
            per_item_cost: number;
            enchant_cost_per_item: number;
            total_per_item_cost: number;
            total_cost: number;
            available_gold: number;
            prefix_affix_name: string | null;
            suffix_affix_name: string | null;
            enchant_can_destroy_item: boolean;
            enchant_has_failure_risk: boolean;
            destination: string | null;
            destination_label: string | null;
            destination_current_slots: number;
            destination_max_slots: number;
            destination_remaining_slots: number;
            effective_craftable_amount: number;
            capped: boolean;
        } | null;
        holy_oil_selected_preview?: {
            items: {
                item: BatchCraftingItemSnapshot | null;
                target_slot_id: number;
                current_stacks: number;
                planned_applications: number;
                resulting_stacks: number;
                maximum_stacks: number;
                exact_gold_dust_cost: number;
            }[];
            selected_oils_available: number;
            applications_planned: number;
            items_affected: number;
            exact_gold_dust_required: number;
            gold_dust_available: number;
            oils_not_applicable: number;
            unapplied_reason: string | null;
            capped: boolean;
        } | null;
        holy_oil_set_preview?: {
            set_name: string;
            items: {
                item: BatchCraftingItemSnapshot | null;
                target_slot_id: number;
                current_stacks: number;
                planned_applications: number;
                resulting_stacks: number;
                maximum_stacks: number;
                exact_gold_dust_cost: number;
            }[];
            selected_oils_available: number;
            applications_planned: number;
            items_affected: number;
            exact_gold_dust_required: number;
            gold_dust_available: number;
            oils_not_applicable: number;
            unapplied_reason: string | null;
            capped: boolean;
        } | null;
        holy_oil_application_results?: {
            target_slot_id: number;
            item: BatchCraftingItemSnapshot;
            initial_stack_count: number;
            actual_applications_completed: number;
            actual_resulting_stack_count: number;
            maximum_stacks: number;
            actual_gold_dust_spent: number;
            oil_applications_consumed: number;
        }[];
        alchemy_amount_preview?: {
            selected_item: BatchCraftingItemSnapshot | null;
            requested_amount: number;
            completed_amount: number;
            remaining_requested_amount: number;
            gold_dust_cost_per_item: number;
            shards_cost_per_item: number;
            total_gold_dust_cost: number;
            total_shards_cost: number;
            available_gold_dust: number;
            available_shards: number;
            bag_current: number;
            bag_max: number;
            bag_remaining: number;
            effective_craftable_amount: number;
            capped: boolean;
        } | null;
        int_stop_details?: {
            character_int: number;
            required_int: number | null;
            missing_int: number | null;
            affixes: {
                id: number;
                name: string;
                type: string;
                int_required: number;
            }[];
        } | null;
        retry_state?: {
            active: boolean;
            failed_count: number;
            delay_seconds: number;
            failure_reason: string | null;
            failure_phase: string | null;
            failure_action: string | null;
        };
        continuation_state?: {
            active: boolean;
            state: "processing" | "waiting" | null;
            reason: string | null;
            message: string | null;
            phase: string | null;
            item: string | null;
            delay_seconds: number;
            next_attempt_at: string | null;
        };
        action_log?: BatchCraftingActionLogEntry[];
        action_history?: BatchCraftingActionLogEntry[];
    };
};

const perPage = 10;

function formatStatus(value?: string | null): string {
    if (!value) {
        return "completed";
    }

    return value.replace(/_/g, " ");
}

function actionHistoryStatusLabel(entry: BatchCraftingActionLogEntry): string {
    const disposition = entry.disposition ?? null;

    const isEnchantedOutput =
        disposition !== null && entry.enchanted_item != null;

    if (isEnchantedOutput) {
        if (disposition === "keep" || disposition.startsWith("keep_best")) {
            return "Enchanted and Kept";
        }

        if (disposition === "sell") {
            return "Enchanted and Sold";
        }

        if (disposition === "destroy") {
            return "Enchanted and Destroyed";
        }

        if (disposition === "list") {
            return "Enchanted and Listed";
        }

        if (disposition === "disenchant") {
            return "Enchanted and Disenchanted";
        }
    }

    return formatStatus(entry.status);
}

function statusBadgeClasses(status?: string | null): string {
    if (status === "failed" || status === "destroyed") {
        return "bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300";
    }

    if (status === "skipped") {
        return "bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300";
    }

    return "bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300";
}

const CLEAN_END_REASONS = new Set([
    "completed_duration",
    "cancelled",
    "amount_reached",
    "craft_set_complete",
    "craft_enchant_set_complete",
    "enchant_set_complete",
    "all_oils_applied",
    "event_goal_complete",
    "skill_maxed",
    "maxed_or_nothing_left",
]);

function isCleanEndReason(reason?: string | null): boolean {
    if (!reason) {
        return true;
    }

    return CLEAN_END_REASONS.has(reason);
}

type CurrencyChartConfig = {
    spentLabel: string;
    spentField: "gold_spent" | "gold_dust_spent" | "shards_spent";
    gainedLabel: string;
    gainedField:
        | "gold_gained"
        | "gold_dust_gained"
        | "shards_gained"
        | "listed_value";
};

function resolveCurrencyChartConfig(
    batch: NonNullable<BatchCraftingStatus["batch"]>,
): CurrencyChartConfig | null {
    const disposition = batch.disposition;
    const spentType = batch.currency?.type ?? "gold";
    const spentField: CurrencyChartConfig["spentField"] =
        spentType === "gold_dust"
            ? "gold_dust_spent"
            : spentType === "shards"
              ? "shards_spent"
              : "gold_spent";
    const spentLabel =
        spentType === "gold_dust"
            ? "Gold Dust Spent"
            : spentType === "shards"
              ? "Shards Spent"
              : "Gold Spent";

    const disenchantsRest =
        disposition === "disenchant" ||
        disposition === "keep_best_disenchant_rest" ||
        (disposition === "keep_highest" &&
            batch.batch_type === "craft_and_enchant");

    const sellsRest =
        disposition === "sell" ||
        disposition === "keep_best_sell_rest" ||
        (disposition === "keep_highest" &&
            ["craft", "alchemy", "trinketry"].includes(batch.batch_type));

    const listsRest = disposition === "list";

    if (disenchantsRest) {
        return {
            spentLabel,
            spentField,
            gainedLabel: "Gold Dust Gained",
            gainedField: "gold_dust_gained",
        };
    }

    if (listsRest) {
        return {
            spentLabel,
            spentField,
            gainedLabel: "Listed Value",
            gainedField: "listed_value",
        };
    }

    if (sellsRest) {
        return {
            spentLabel,
            spentField,
            gainedLabel: "Gold Gained",
            gainedField: "gold_gained",
        };
    }

    return null;
}

const INT_ENCHANT_LINKS: { href: string; label: string }[] = [
    {
        href: "/information/enchanting?table-filters[types]=0",
        label: "Stat based enchants",
    },
    {
        href: "/information/enchanting?table-filters[types]=15",
        label: "Spell crafting - raises INT",
    },
    {
        href: "/information/enchanting?table-filters[types]=16",
        label: "Enchantment crafting - raises INT",
    },
    {
        href: "/information/crafting?filter=wand",
        label: "Wands",
    },
    {
        href: "/information/crafting?filter=stave",
        label: "Staves",
    },
    {
        href: "/information/crafting?filter=spell-damage",
        label: "Spell Damage",
    },
];

function IntEnchantLinksList() {
    return (
        <ul className="list-disc space-y-1 pl-5">
            {INT_ENCHANT_LINKS.map((link) => (
                <li key={link.href}>
                    <a
                        href={link.href}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="underline hover:no-underline focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        {link.label}
                    </a>
                </li>
            ))}
        </ul>
    );
}

function itemForColor(item: BatchCraftingItemSnapshot) {
    return {
        name: item.name ?? "Unknown item",
        type: item.type ?? "item",
        affix_count: item.affix_count ?? 0,
        is_unique: item.is_unique ?? false,
        holy_stacks_applied: item.holy_stacks_applied ?? 0,
        is_mythic: item.is_mythic ?? false,
        is_cosmic: item.is_cosmic ?? false,
    };
}

function primaryItem(
    entry: BatchCraftingActionLogEntry,
): BatchCraftingItemSnapshot | null {
    return (
        entry.disenchanted_item ??
        entry.kept_item ??
        entry.sold_item ??
        entry.destroyed_item ??
        entry.listed_item ??
        entry.enchanted_item ??
        entry.crafted_item ??
        entry.alchemy_item ??
        entry.trinketry_item ??
        entry.oil_application?.target_item ??
        null
    );
}

export function ProgressBar({
    label,
    current,
    max,
    percent,
    barClassName = "bg-orange-600",
}: {
    label: string;
    current: number;
    max: number;
    percent: number;
    barClassName?: string;
}) {
    return (
        <div>
            <div className="mb-1 flex justify-between text-xs text-orange-700 dark:text-white">
                <span>{label}</span>
                <span>
                    {current} / {max}
                </span>
            </div>
            <div className="h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                <div
                    className={"h-1.5 rounded-full " + barClassName}
                    style={{ width: `${percent}%` }}
                    role="progressbar"
                    aria-valuemin={0}
                    aria-valuemax={100}
                    aria-valuenow={percent}
                />
            </div>
        </div>
    );
}

export default class BatchCraftingStatusDisplay extends React.Component<
    BatchCraftingStatusDisplayProps,
    BatchCraftingStatusDisplayState
> {
    public constructor(props: BatchCraftingStatusDisplayProps) {
        super(props);

        this.state = {
            page: 1,
            openItemId: null,
            openSetSlotId: null,
            openSnapshot: null,
            affixDetailsModalAffix: null,
            affixDetailsModalOpen: false,
        };
    }

    actionLog() {
        return this.props.status.batch?.action_log ?? [];
    }

    totalPages() {
        return Math.max(1, Math.ceil(this.actionLog().length / perPage));
    }

    currentPage() {
        return Math.min(this.state.page, this.totalPages());
    }

    entries() {
        const actionLog = this.actionLog();
        const currentPage = this.currentPage();
        const start = (currentPage - 1) * perPage;

        return actionLog
            .slice()
            .reverse()
            .slice(start, start + perPage);
    }

    setPage(page: number) {
        this.setState({
            page,
        });
    }

    setOpenItemId(
        openItemId: number | null,
        openSetSlotId: number | null = null,
    ) {
        this.setState({
            openItemId,
            openSetSlotId,
        });
    }

    setOpenSnapshot(openSnapshot: BatchCraftingItemSnapshot | null) {
        this.setState({
            openSnapshot,
        });
    }

    openAffixDetails(affix: any) {
        this.setState({
            affixDetailsModalAffix: affix,
            affixDetailsModalOpen: true,
        });
    }

    closeAffixDetails() {
        this.setState({
            affixDetailsModalAffix: null,
            affixDetailsModalOpen: false,
        });
    }

    renderAffixName(
        name: string | null | undefined,
        affix: any | null | undefined,
    ) {
        if (!name) {
            return null;
        }

        if (!affix) {
            return <span>{name}</span>;
        }

        return (
            <button
                type="button"
                className="hover:underline focus:outline-none focus:ring-2 focus:ring-blue-500"
                onClick={() => this.openAffixDetails(affix)}
            >
                {name}
            </button>
        );
    }

    renderAffixNamesList(
        names: string[] | undefined,
        ids: number[] | null | undefined,
        affixes: any[] | null | undefined,
    ) {
        if (!names || names.length === 0) {
            return "None";
        }

        const affixList = affixes ?? [];
        const idList = ids ?? [];

        return (
            <>
                {names.map((name, index) => {
                    const affixId = idList[index];
                    const matchedAffix =
                        typeof affixId === "number"
                            ? (affixList.find(
                                  (affix) => affix.id === affixId,
                              ) ?? null)
                            : null;

                    return (
                        <React.Fragment key={`${name}-${index}`}>
                            {index > 0 ? ", " : ""}
                            {this.renderAffixName(name, matchedAffix)}
                        </React.Fragment>
                    );
                })}
            </>
        );
    }

    renderItem(item: BatchCraftingItemSnapshot | null | undefined) {
        if (!item || !item.name) {
            return <span>None</span>;
        }

        const text = (
            <ItemNameColorationText
                item={itemForColor(item)}
                custom_width={false}
                additional_css={""}
            />
        );

        // set_slot_id signals that the item now lives in a real, viewable
        // Crafted Items Set slot. InventoryUseDetails is keyed by the
        // underlying Item id (item_id) for the details it displays, but
        // duplicate non-enchanted items can share the same catalog item_id
        // across several SetSlots, so set_slot_id is also passed through as
        // an exact-slot hint (backend falls back to its old item_id-only
        // lookup when no slot id is supplied, so every other existing caller
        // of this modal is unaffected).
        const hasSetSlot =
            item.set_slot_id !== null &&
            typeof item.set_slot_id !== "undefined";
        const liveItemId =
            item.item_id_for_modal ??
            (hasSetSlot ? (item.item_id ?? null) : null);
        const hasLiveSlot =
            (item.slot_id_for_modal !== null &&
                typeof item.slot_id_for_modal !== "undefined") ||
            hasSetSlot;

        if ((item.can_view || hasSetSlot) && liveItemId && hasLiveSlot) {
            return (
                <button
                    type="button"
                    className="text-left hover:underline focus:outline-none focus:ring-2 focus:ring-blue-500"
                    aria-label={`View details for ${item.name}`}
                    onClick={() =>
                        this.setOpenItemId(
                            liveItemId,
                            hasSetSlot ? (item.set_slot_id ?? null) : null,
                        )
                    }
                >
                    {text}
                </button>
            );
        }

        return (
            <button
                type="button"
                className="text-left hover:underline focus:outline-none focus:ring-2 focus:ring-blue-500"
                aria-label={`View snapshot details for ${item.name}`}
                onClick={() => this.setOpenSnapshot(item)}
            >
                {text}
            </button>
        );
    }

    renderBatchSummary(batch: NonNullable<BatchCraftingStatus["batch"]>) {
        if (
            batch.requested_amount &&
            typeof batch.completed_amount === "number"
        ) {
            const remaining = Math.max(
                0,
                batch.requested_amount - batch.completed_amount,
            );

            return (
                <p className="rounded-sm bg-gray-100 p-3 text-sm font-semibold text-gray-800 dark:bg-gray-900 dark:text-gray-200">
                    Crafting {batch.completed_amount.toLocaleString()} of{" "}
                    {batch.requested_amount.toLocaleString()} ·{" "}
                    {remaining.toLocaleString()} remaining
                </p>
            );
        }

        if (batch.event_mode) {
            return (
                <p className="rounded-sm bg-blue-50 p-3 text-sm font-semibold text-blue-900 dark:bg-blue-950 dark:text-blue-100">
                    {batch.event_current_phase_label ??
                        "Waiting for next event batch tick"}
                </p>
            );
        }

        return (
            <p className="rounded-sm bg-gray-100 p-3 text-sm font-semibold text-gray-800 dark:bg-gray-900 dark:text-gray-200">
                {batch.next_action ?? batch.batch_label}
            </p>
        );
    }

    renderCurrencyDetails(batch: NonNullable<BatchCraftingStatus["batch"]>) {
        const currencyType = batch.currency?.type ?? "gold";

        if (currencyType === "gold_dust") {
            return (
                <>
                    <dt className="font-semibold">Gold Dust Spent</dt>
                    <dd>{formatNumber(batch.gold_dust_spent_total ?? 0)}</dd>
                    <dt className="font-semibold">Gold Dust Gained</dt>
                    <dd>{formatNumber(batch.gold_dust_gained_total ?? 0)}</dd>
                    <dt className="font-semibold">Gold Dust Left</dt>
                    <dd>{formatNumber(batch.gold_dust_left ?? 0)}</dd>
                </>
            );
        }

        if (currencyType === "shards") {
            return (
                <>
                    <dt className="font-semibold">Shards Left</dt>
                    <dd>{formatNumber(batch.currency?.amount ?? 0)}</dd>
                </>
            );
        }

        return (
            <>
                <dt className="font-semibold">Gold Spent</dt>
                <dd>{formatNumber(batch.gold_spent_total ?? 0)}</dd>
                <dt className="font-semibold">Gold Gained</dt>
                <dd>{formatNumber(batch.gold_gained_total ?? 0)}</dd>
                <dt className="font-semibold">Gold Left</dt>
                <dd>{formatNumber(batch.gold_left ?? 0)}</dd>
            </>
        );
    }

    renderGoldDustGainedIfApplicable(
        batch: NonNullable<BatchCraftingStatus["batch"]>,
    ) {
        if (batch.currency?.type === "gold_dust") {
            return null;
        }

        const gained = batch.gold_dust_gained_total ?? 0;

        if (gained <= 0) {
            return null;
        }

        return (
            <>
                <dt className="font-semibold">Gold Dust Gained</dt>
                <dd>{formatNumber(gained)}</dd>
            </>
        );
    }

    renderDetailGrid(
        rows: {
            label: string;
            value: React.ReactNode;
            show?: boolean;
        }[],
    ) {
        const visibleRows = rows.filter((row) => row.show ?? true);

        if (visibleRows.length === 0) {
            return null;
        }

        return (
            <dl className="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
                {visibleRows.map((row) => (
                    <React.Fragment key={row.label}>
                        <dt className="font-semibold">{row.label}</dt>
                        <dd>{row.value}</dd>
                    </React.Fragment>
                ))}
            </dl>
        );
    }

    statusText(
        batch: NonNullable<BatchCraftingStatus["batch"]>,
        isActive: boolean,
    ) {
        return isActive ? "Running" : formatStatus(batch.ended_reason);
    }

    renderSkillsList(skills: BatchCraftingSkillData[] | undefined) {
        const visibleSkills = (skills ?? []).filter((skill) => !skill.is_maxed);

        if (visibleSkills.length === 0) {
            return null;
        }

        return (
            <section>
                <h4 className="mb-2 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                    Skills
                </h4>
                <div className="grid gap-2">
                    {visibleSkills.map((skill) => (
                        <div key={skill.key}>
                            <div className="mb-1 flex justify-between text-xs font-medium text-orange-700 dark:text-white">
                                <span>
                                    {skill.name} Skill XP (LV: {skill.level}/
                                    {skill.max_level})
                                </span>
                                <span>
                                    {formatNumber(skill.current_xp)}/
                                    {formatNumber(skill.next_level_xp)}
                                </span>
                            </div>
                            <div
                                className="h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700"
                                role="progressbar"
                                aria-valuemin={0}
                                aria-valuemax={100}
                                aria-valuenow={skill.xp_percent}
                                aria-label={`${skill.name} experience progress`}
                            >
                                <div
                                    className="h-1.5 rounded-full bg-orange-600"
                                    style={{ width: `${skill.xp_percent}%` }}
                                />
                            </div>
                        </div>
                    ))}
                </div>
            </section>
        );
    }

    renderUsefulCounts(batch: NonNullable<BatchCraftingStatus["batch"]>) {
        const rows = [
            { label: "Crafted", value: batch.counts.crafted },
            { label: "Enchanted", value: batch.counts.enchanted ?? 0 },
            { label: "Kept", value: batch.counts.kept },
            { label: "Sold", value: batch.counts.sold },
            { label: "Destroyed", value: batch.counts.destroyed },
            { label: "Listed", value: batch.counts.listed },
            { label: "Disenchanted", value: batch.counts.disenchanted ?? 0 },
            {
                label: "Alchemy Processed",
                value: batch.counts.alchemy_processed ?? 0,
            },
            {
                label: "Trinketry Processed",
                value: batch.counts.trinketry_processed ?? 0,
            },
            { label: "Applied", value: batch.counts.applied },
            { label: "Skipped", value: batch.counts.skipped },
            { label: "Failed", value: batch.counts.failed },
            ...(batch.disposition === "list"
                ? [
                      {
                          label: "Listing Price Per Item",
                          value: batch.listing_price_per_item ?? 0,
                      },
                      {
                          label: "Total Listed Value",
                          value: batch.total_listed_value ?? 0,
                      },
                      {
                          label: "Potential Net After Market Tax",
                          value: batch.potential_seller_net ?? 0,
                      },
                  ]
                : []),
        ].filter((row) => row.value > 0);

        if (rows.length === 0) {
            return null;
        }

        return (
            <dl className="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
                {rows.map((row) => (
                    <React.Fragment key={row.label}>
                        <dt className="font-semibold">{row.label}</dt>
                        <dd>{formatNumber(row.value)}</dd>
                    </React.Fragment>
                ))}
            </dl>
        );
    }

    // Shared listing-summary rows for the List disposition, spread into
    // renderDetailGrid-style row arrays (Craft and Enchant Amount/Experience,
    // Alchemy Amount/Experience, Holy Oils Selected). Only meaningful when
    // the batch's disposition is "list".
    listingSummaryRows(batch: NonNullable<BatchCraftingStatus["batch"]>): {
        label: string;
        value: React.ReactNode;
        show?: boolean;
    }[] {
        if (batch.disposition !== "list") {
            return [];
        }

        return [
            {
                label: "Listing Price Per Item",
                value: formatNumber(batch.listing_price_per_item ?? 0),
            },
            {
                label: "Total Listed Value",
                value: formatNumber(batch.total_listed_value ?? 0),
            },
            {
                label: "Potential Net After Market Tax",
                value: formatNumber(batch.potential_seller_net ?? 0),
            },
        ];
    }

    // Same data as listingSummaryRows, but as direct dt/dd children for the
    // hand-built <dl> panels (Craft and Enchant Set, Holy Oils Set) that
    // don't go through renderDetailGrid.
    renderListingValueDtDd(batch: NonNullable<BatchCraftingStatus["batch"]>) {
        if (batch.disposition !== "list") {
            return null;
        }

        return (
            <>
                <dt className="font-semibold">Listing Price Per Item</dt>
                <dd>{formatNumber(batch.listing_price_per_item ?? 0)}</dd>
                <dt className="font-semibold">Total Listed Value</dt>
                <dd>{formatNumber(batch.total_listed_value ?? 0)}</dd>
                <dt className="font-semibold">
                    Potential Net After Market Tax
                </dt>
                <dd>{formatNumber(batch.potential_seller_net ?? 0)}</dd>
            </>
        );
    }

    renderActionHistory(
        isActive: boolean,
        endedReason: string | null | undefined,
        hideActionType: boolean = false,
    ) {
        const actionLog = this.actionLog();

        if (actionLog.length === 0) {
            return null;
        }

        const currentPage = this.currentPage();
        const totalPages = this.totalPages();
        const entryCount = actionLog.length;
        const defaultOpen = isActive || !isCleanEndReason(endedReason);

        return (
            <details open={defaultOpen}>
                <summary className="cursor-pointer text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500">
                    Action History ({entryCount}{" "}
                    {entryCount === 1 ? "entry" : "entries"})
                </summary>
                <p className="mb-2 mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Every action for this batch is recorded below. Counts above
                    are the source of truth for full batch progress.
                </p>
                <ul className="grid gap-3">
                    {this.entries().map((entry, index) => {
                        const item = primaryItem(entry);
                        const statusLabel = actionHistoryStatusLabel(entry);
                        const noItemProduced =
                            !item &&
                            !(entry.crafted_item && entry.enchanted_item);
                        const rows: {
                            label: string;
                            value: React.ReactNode;
                            className?: string;
                            valueClassName?: string;
                        }[] = [
                            {
                                label: "Time",
                                value: formatLocalDateTime(entry.ts),
                            },
                            {
                                label: "Status",
                                value: (
                                    <span
                                        className={
                                            "inline-flex rounded-full px-2 py-0.5 text-[0.7rem] font-semibold capitalize " +
                                            statusBadgeClasses(entry.status)
                                        }
                                    >
                                        {statusLabel}
                                    </span>
                                ),
                            },
                        ];

                        if (!hideActionType) {
                            rows.push({
                                label: "Action Type",
                                value: formatStatus(entry.action_type),
                                valueClassName: "capitalize",
                            });
                        }

                        if (entry.phase) {
                            rows.push({
                                label: "Phase",
                                value: formatStatus(entry.phase),
                                valueClassName: "capitalize",
                            });
                        }

                        if (entry.crafted_item && entry.enchanted_item) {
                            rows.push({
                                label: "Attempted Item",
                                value: this.renderItem(entry.crafted_item),
                            });
                            rows.push({
                                label: "Result Item",
                                value: this.renderItem(entry.enchanted_item),
                            });
                        } else {
                            rows.push({
                                label: "Item",
                                value: noItemProduced
                                    ? "No item produced"
                                    : this.renderItem(item),
                            });
                        }

                        if (entry.prefix_affix_name) {
                            rows.push({
                                label: "Prefix",
                                value: (
                                    <>
                                        {this.renderAffixName(
                                            entry.prefix_affix_name,
                                            entry.prefix_affix,
                                        )}
                                        {typeof entry.prefix_applied ===
                                        "boolean"
                                            ? entry.prefix_applied
                                                ? " (applied)"
                                                : " (not applied)"
                                            : ""}
                                    </>
                                ),
                            });
                        }

                        if (entry.suffix_affix_name) {
                            rows.push({
                                label: "Suffix",
                                value: (
                                    <>
                                        {this.renderAffixName(
                                            entry.suffix_affix_name,
                                            entry.suffix_affix,
                                        )}
                                        {typeof entry.suffix_applied ===
                                        "boolean"
                                            ? entry.suffix_applied
                                                ? " (applied)"
                                                : " (not applied)"
                                            : ""}
                                    </>
                                ),
                            });
                        }

                        if (entry.oil_application?.oil_item) {
                            rows.push({
                                label: "Oil",
                                value: this.renderItem(
                                    entry.oil_application.oil_item,
                                ),
                            });
                        }

                        if (entry.gold_spent) {
                            rows.push({
                                label: "Gold Spent",
                                value: formatNumber(entry.gold_spent),
                            });
                        }

                        if (entry.gold_gained) {
                            rows.push({
                                label: "Gold Gained",
                                value: formatNumber(entry.gold_gained),
                            });
                        }

                        if (entry.gold_dust_gained) {
                            rows.push({
                                label: "Gold Dust Gained",
                                value: formatNumber(entry.gold_dust_gained),
                            });
                        }

                        if (entry.destination_set) {
                            rows.push({
                                label: "Destination Set",
                                value: entry.destination_set,
                            });
                        }

                        if (entry.created_in_crafted_items_set) {
                            rows.push({
                                label: "Created In Crafted Items Set",
                                value: "Yes",
                            });
                        }

                        if (entry.listed_price) {
                            rows.push({
                                label: "Listed Price",
                                value: formatNumber(entry.listed_price),
                            });
                        }

                        if (entry.failure) {
                            rows.push({
                                label: "Failure Reason",
                                value: entry.failure,
                                valueClassName:
                                    "text-red-700 dark:text-red-300 break-words",
                            });
                        }

                        return (
                            <li
                                key={`${entry.ts}-${index}`}
                                className="border-b border-gray-200 pb-2 dark:border-gray-700"
                                aria-label={`${entry.action_type ?? "batch action"} ${item?.name ?? "item"} ${statusLabel}`}
                            >
                                <dl className="mt-1 grid grid-cols-1 gap-1 text-xs text-gray-600 dark:text-gray-400 sm:grid-cols-2">
                                    {rows.map((row) => (
                                        <React.Fragment key={row.label}>
                                            <dt
                                                className={
                                                    "font-semibold " +
                                                    (row.className ?? "")
                                                }
                                            >
                                                {row.label}
                                            </dt>
                                            <dd
                                                className={
                                                    (row.valueClassName ?? "") +
                                                    " " +
                                                    (row.className ?? "")
                                                }
                                            >
                                                {row.value}
                                            </dd>
                                        </React.Fragment>
                                    ))}
                                </dl>
                            </li>
                        );
                    })}
                </ul>
                <div className="mt-3 flex items-center justify-between gap-3">
                    <button
                        type="button"
                        className="rounded border border-gray-300 px-3 py-1 text-sm disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600"
                        disabled={currentPage === 1}
                        onClick={() => this.setPage(currentPage - 1)}
                    >
                        Previous
                    </button>
                    <span className="text-xs text-gray-500 dark:text-gray-400">
                        Page {currentPage} of {totalPages}
                    </span>
                    <button
                        type="button"
                        className="rounded border border-gray-300 px-3 py-1 text-sm disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600"
                        disabled={currentPage === totalPages}
                        onClick={() => this.setPage(currentPage + 1)}
                    >
                        Next
                    </button>
                </div>
            </details>
        );
    }

    renderCharts(batch: NonNullable<BatchCraftingStatus["batch"]>) {
        const chartPoints = batch.chart_points;

        if (!chartPoints) {
            return null;
        }

        const outcomeLines: ChartLine[] = [
            {
                label: "Success",
                color: "#22c55e",
                data: chartPoints.outcomes.map((point) => ({
                    label: String(point.tick),
                    value: point.success,
                })),
            },
            {
                label: "Failure",
                color: "#ef4444",
                data: chartPoints.outcomes.map((point) => ({
                    label: String(point.tick),
                    value: point.failure,
                })),
            },
        ];

        const currencyConfig = resolveCurrencyChartConfig(batch);

        if (!currencyConfig) {
            return (
                <section className="grid gap-4">
                    <BatchLineChart
                        title="Success vs Failure"
                        lines={outcomeLines}
                        yAxisLabel="Count"
                    />
                </section>
            );
        }

        const currencyLines: ChartLine[] = [
            {
                label: currencyConfig.spentLabel,
                color: "#2563eb",
                data: chartPoints.currency.map((point) => ({
                    label: String(point.tick),
                    value: point[currencyConfig.spentField] ?? 0,
                })),
            },
            {
                label: currencyConfig.gainedLabel,
                color: "#d97706",
                data: chartPoints.currency.map((point) => ({
                    label: String(point.tick),
                    value: point[currencyConfig.gainedField] ?? 0,
                })),
            },
        ];

        return (
            <section className="grid gap-4 sm:grid-cols-2">
                <BatchLineChart
                    title={`${currencyConfig.spentLabel} vs ${currencyConfig.gainedLabel}`}
                    lines={currencyLines}
                    yAxisLabel="Amount"
                />
                <BatchLineChart
                    title="Success vs Failure"
                    lines={outcomeLines}
                    yAxisLabel="Count"
                />
            </section>
        );
    }

    renderActionButtons(isActive: boolean, isSaving: boolean) {
        const { onCancel, onClose, onDismiss } = this.props;

        return (
            <div className="flex flex-col items-center justify-center gap-2 md:flex-row">
                {isActive ? (
                    <DangerButton
                        button_label={"Cancel Batch"}
                        on_click={onCancel}
                        disabled={isSaving}
                        additional_css={"w-full md:w-auto"}
                    />
                ) : (
                    <PrimaryButton
                        button_label={"Dismiss"}
                        on_click={onDismiss}
                        disabled={isSaving}
                        additional_css={"w-full md:w-auto"}
                    />
                )}
                {onClose && batch.status === "running" ? (
                    <DangerButton
                        button_label={"Close"}
                        on_click={onClose}
                        additional_css={"w-full md:w-auto"}
                        disabled={isSaving}
                    />
                ) : null}
            </div>
        );
    }

    renderCompletionSummary(
        isActive: boolean,
        summary: "all" | "some" | "none" | null | undefined,
        labels: { all: string; some: string; none: string },
    ) {
        if (isActive || !summary) {
            return null;
        }

        const text =
            summary === "all"
                ? labels.all
                : summary === "some"
                  ? labels.some
                  : labels.none;

        return (
            <p
                role="status"
                className="rounded-sm bg-gray-100 p-3 text-sm font-semibold text-gray-800 dark:bg-gray-900 dark:text-gray-200"
            >
                {text}
            </p>
        );
    }

    renderIntTooLowWarning(
        batch: NonNullable<BatchCraftingStatus["batch"]>,
        isActive: boolean,
    ) {
        if (isActive || batch.ended_reason !== "int_too_low_for_enchanting") {
            return null;
        }

        const details = batch.int_stop_details;

        return (
            <WarningAlert additional_css="my-2">
                <div className="space-y-2">
                    <h3 className="font-bold">
                        Batch Crafting stopped: Intelligence too low
                    </h3>
                    {details !== null &&
                    details !== undefined &&
                    details.required_int !== null &&
                    details.missing_int !== null &&
                    details.affixes.length > 0 ? (
                        <>
                            <dl className="grid grid-cols-2 gap-2 text-sm">
                                <dt className="font-semibold">Required INT</dt>
                                <dd>{details.required_int.toLocaleString()}</dd>
                                <dt className="font-semibold">Your INT</dt>
                                <dd>
                                    {details.character_int.toLocaleString()}
                                </dd>
                                <dt className="font-semibold">Missing INT</dt>
                                <dd>{details.missing_int.toLocaleString()}</dd>
                            </dl>
                            <h4 className="font-semibold">
                                Blocking enchantments
                            </h4>
                            <ul className="list-disc space-y-1 pl-5">
                                {details.affixes.map((affix) => (
                                    <li key={affix.id}>
                                        {affix.name} ({affix.type}) requires{" "}
                                        {affix.int_required.toLocaleString()}{" "}
                                        INT
                                    </li>
                                ))}
                            </ul>
                        </>
                    ) : (
                        <p>
                            The exact blocking enchantment details could not be
                            resolved.
                        </p>
                    )}
                    <h4 className="font-semibold">
                        Ways to raise Intelligence
                    </h4>
                    <IntEnchantLinksList />
                </div>
            </WarningAlert>
        );
    }

    renderIntPreemptiveInfo(
        batch: NonNullable<BatchCraftingStatus["batch"]>,
        isActive: boolean,
    ) {
        if (!isActive || batch.batch_type !== "craft_and_enchant") {
            return null;
        }

        return (
            <InfoAlert additional_css="text-sm my-2">
                <div className="space-y-2">
                    <p>
                        Enchanting requires enough Intelligence for the selected
                        enchantment, or this batch will stop early. Raise INT
                        ahead of time to avoid interruptions.
                    </p>
                    <IntEnchantLinksList />
                </div>
            </InfoAlert>
        );
    }

    completionPercent(current: number, max: number): number {
        if (max <= 0) {
            return 0;
        }

        return Math.min(100, Math.max(0, Math.floor((current / max) * 100)));
    }

    /**
     * Single shared continuation renderer used by every active finite panel, backed by
     * the persisted server continuation_state (processing/waiting, reason, phase,
     * item, and next_attempt_at). Replaces the old renderRetryAlert()/
     * renderProcessingStatusText() combination, which only covered plain failures and
     * went silent (and looked frozen) during replacement-crafting waits and
     * remaining-work waits.
     */
    renderContinuation(isActive: boolean) {
        if (!isActive) {
            return null;
        }

        const continuationState = this.props.status.batch?.continuation_state;

        if (!continuationState || !continuationState.active) {
            return null;
        }

        const { state, message, phase, item } = continuationState;

        return (
            <InfoAlert additional_css="text-sm my-2">
                <div className="space-y-1" role="status" aria-live="polite">
                    {state === "processing" ? (
                        <p>
                            Batch Crafting is processing the next attempt now.
                        </p>
                    ) : null}
                    {state === "waiting" ? (
                        <p>
                            {message ??
                                "The next attempt is waiting for the Batch Crafting worker."}
                        </p>
                    ) : null}
                    {phase ? <p>Current phase: {startCase(phase)}</p> : null}
                    {item ? <p>Current item: {item}</p> : null}
                </div>
            </InfoAlert>
        );
    }

    renderOutputDestinationCapacity(
        batch: NonNullable<BatchCraftingStatus["batch"]>,
    ) {
        const isFiniteKeepOutputMode =
            batch.disposition === "keep" &&
            ((batch.batch_type === "craft" &&
                (batch.mode === "specific_item" ||
                    batch.mode === "craft_set")) ||
                (batch.batch_type === "craft_and_enchant" &&
                    (batch.mode === "specific_item" ||
                        batch.mode === "craft_enchant_set")));

        if (!isFiniteKeepOutputMode || !batch.output_destination) {
            return null;
        }

        if (batch.output_destination === "inventory") {
            const current = batch.inventory_count ?? 0;
            const max = batch.inventory_max ?? 0;

            return (
                <ProgressBar
                    label="Inventory Used"
                    current={current}
                    max={max}
                    percent={
                        max > 0
                            ? Math.min(100, Math.floor((current / max) * 100))
                            : 0
                    }
                    barClassName="bg-regent-st-blue-500"
                />
            );
        }

        if (batch.output_destination === "inventory_set" && batch.output_set) {
            const current = batch.output_set.current_slots ?? 0;
            const max = batch.output_set.max_slots ?? 0;

            return (
                <ProgressBar
                    label={`${batch.output_set.name} Used`}
                    current={current}
                    max={max}
                    percent={
                        max > 0
                            ? Math.min(100, Math.floor((current / max) * 100))
                            : 0
                    }
                    barClassName="bg-regent-st-blue-500"
                />
            );
        }

        if (
            batch.output_destination === "crafted_items_set" &&
            batch.batch_crafting_set
        ) {
            const current = batch.batch_crafting_set.current_slots ?? 0;
            const max = batch.batch_crafting_set.max_slots ?? 0;

            return (
                <ProgressBar
                    label="Crafted Items Set Used"
                    current={current}
                    max={max}
                    percent={
                        batch.batch_crafting_set.percent ??
                        (max > 0
                            ? Math.min(100, Math.floor((current / max) * 100))
                            : 0)
                    }
                    barClassName="bg-regent-st-blue-500"
                />
            );
        }

        return null;
    }

    renderAmountPreview(batch: NonNullable<BatchCraftingStatus["batch"]>) {
        const preview = batch.amount_preview;

        if (!preview) {
            return null;
        }

        const enchantAffixes = batch.enchant_affixes ?? [];
        const previewPrefixAffix =
            enchantAffixes.find(
                (affix: any) => affix.name === preview.prefix_affix_name,
            ) ?? null;
        const previewSuffixAffix =
            enchantAffixes.find(
                (affix: any) => affix.name === preview.suffix_affix_name,
            ) ?? null;

        return (
            <div className="grid gap-3">
                {preview.destination_label ? (
                    <InfoAlert additional_css="text-sm my-2">
                        Kept output for this batch will be placed in{" "}
                        {preview.destination_label}.
                    </InfoAlert>
                ) : null}
                <h5 className="font-semibold">Craft Amount</h5>
                <div className="border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block"></div>
                <dl className="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
                    <dt className="font-semibold">Selected Item</dt>
                    <dd>{this.renderItem(preview.selected_item)}</dd>
                    {preview.prefix_affix_name ? (
                        <>
                            <dt className="font-semibold">Prefix</dt>
                            <dd>
                                {this.renderAffixName(
                                    preview.prefix_affix_name,
                                    previewPrefixAffix,
                                )}
                            </dd>
                        </>
                    ) : null}
                    {preview.suffix_affix_name ? (
                        <>
                            <dt className="font-semibold">Suffix</dt>
                            <dd>
                                {this.renderAffixName(
                                    preview.suffix_affix_name,
                                    previewSuffixAffix,
                                )}
                            </dd>
                        </>
                    ) : null}
                    <dt className="font-semibold">Per Item Cost</dt>
                    <dd>{formatNumber(preview.total_per_item_cost)}</dd>
                    <dt className="font-semibold">Total Cost</dt>
                    <dd>{formatNumber(preview.total_cost)}</dd>
                    <dt className="font-semibold">Available Gold</dt>
                    <dd>{formatNumber(preview.available_gold)}</dd>
                    {preview.destination_label ? (
                        <>
                            <dt className="font-semibold">
                                {preview.destination_label} Space
                            </dt>
                            <dd>
                                {formatNumber(
                                    preview.destination_current_slots,
                                )}{" "}
                                / {formatNumber(preview.destination_max_slots)}{" "}
                                (
                                {formatNumber(
                                    preview.destination_remaining_slots,
                                )}{" "}
                                remaining)
                            </dd>
                        </>
                    ) : null}
                    <dt className="font-semibold">
                        Effective Craftable Amount
                    </dt>
                    <dd>
                        {formatNumber(preview.effective_craftable_amount)} of{" "}
                        {formatNumber(preview.remaining_requested_amount)}
                    </dd>
                </dl>
                {preview.enchant_has_failure_risk ? (
                    <WarningAlert additional_css="my-2">
                        Enchanting can fail and destroy the item because your
                        Enchanting level is below 400. Gold is still spent even
                        if that happens.
                    </WarningAlert>
                ) : null}
                {preview.capped ? (
                    <WarningAlert additional_css="my-2">
                        This batch can only complete{" "}
                        {formatNumber(preview.effective_craftable_amount)} of
                        the requested{" "}
                        {formatNumber(preview.remaining_requested_amount)} items
                        with your current gold
                        {preview.destination_label
                            ? ` and ${preview.destination_label} space.`
                            : "."}
                    </WarningAlert>
                ) : null}
            </div>
        );
    }

    renderSpecificCraftingPanel(
        batch: NonNullable<BatchCraftingStatus["batch"]>,
        isActive: boolean,
        isSaving: boolean,
    ) {
        const requested = batch.requested_amount ?? 0;
        const completed = batch.completed_amount ?? 0;
        const remaining =
            batch.remaining_amount ?? Math.max(0, requested - completed);
        const item = batch.current_item_snapshot;

        return (
            <div className="space-y-4 text-sm" role="status" aria-live="polite">
                <div>
                    <h3 className="text-lg font-semibold">
                        {isActive ? "Craft Amount" : "Craft Amount Complete"}
                    </h3>
                    <p className="mt-1 text-gray-700 dark:text-gray-300">
                        {isActive ? (
                            <React.Fragment>
                                Crafting {formatNumber(requested)} of{" "}
                                {item?.name
                                    ? this.renderItem(item)
                                    : (batch.current_item_name ??
                                      "the selected item")}
                            </React.Fragment>
                        ) : (
                            `Crafted ${formatNumber(completed)} of ${formatNumber(requested)}`
                        )}
                    </p>
                </div>

                {this.renderContinuation(isActive)}

                {this.renderCompletionSummary(
                    isActive,
                    batch.completion_summary,
                    {
                        all: "Crafted all requested items.",
                        some: "Crafted some of the requested items. See the action history below for details.",
                        none: "Crafted none of the requested items. See the action history below for details.",
                    },
                )}

                <ProgressBar
                    label="Batch Progress"
                    current={completed}
                    max={requested}
                    percent={this.completionPercent(completed, requested)}
                    barClassName="bg-orange-600"
                />

                <dl className="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
                    <dt className="font-semibold">Status</dt>
                    <dd>{this.statusText(batch, isActive)}</dd>
                    <dt className="font-semibold">Selected Item</dt>
                    <dd>{this.renderItem(item)}</dd>
                    <dt className="font-semibold">Current Item</dt>
                    <dd>{this.renderItem(item)}</dd>
                    <dt className="font-semibold">Requested</dt>
                    <dd>{formatNumber(requested)}</dd>
                    <dt className="font-semibold">Completed</dt>
                    <dd>{formatNumber(completed)}</dd>
                    <dt className="font-semibold">Remaining</dt>
                    <dd>{formatNumber(remaining)}</dd>
                    <dt className="font-semibold">Gold Spent</dt>
                    <dd>
                        {formatNumber(
                            batch.gold_spent_total ?? batch.gold_spent ?? 0,
                        )}
                    </dd>
                    <dt className="font-semibold">Gold Left</dt>
                    <dd>{formatNumber(batch.gold_left ?? 0)}</dd>
                </dl>

                {this.renderOutputDestinationCapacity(batch)}

                {this.renderAmountPreview(batch)}

                {this.renderSkillsList(batch.skills)}
                {this.renderUsefulCounts(batch)}
                {this.renderActionHistory(isActive, batch.ended_reason)}

                {this.renderActionButtons(isActive, isSaving)}

                {this.renderOpenModals()}
            </div>
        );
    }

    renderExperienceRateLabel(
        batch: NonNullable<BatchCraftingStatus["batch"]>,
    ) {
        if (!batch.experience_rate_label) {
            return null;
        }

        return (
            <p className="text-xs font-medium text-gray-500 dark:text-gray-400">
                {batch.experience_rate_label}
            </p>
        );
    }

    renderCraftExperiencePanel(
        batch: NonNullable<BatchCraftingStatus["batch"]>,
        isActive: boolean,
        isSaving: boolean,
    ) {
        const item = batch.current_item_snapshot;
        const set = batch.batch_crafting_set;
        const movesToCraftedItemsSet = [
            "keep",
            "keep_highest",
            "keep_best_sell_rest",
            "keep_best_destroy_rest",
            "keep_best_disenchant_rest",
        ].includes(batch.disposition);

        return (
            <div className="space-y-4 text-sm" role="status" aria-live="polite">
                <div>
                    <h3 className="text-lg font-semibold">
                        Crafting for Experience
                    </h3>
                    {this.renderExperienceRateLabel(batch)}
                    <p className="mt-1 text-gray-700 dark:text-gray-300">
                        {item?.name ? (
                            <React.Fragment>
                                Currently crafting {this.renderItem(item)}
                            </React.Fragment>
                        ) : (
                            "Preparing to craft the next eligible item."
                        )}
                    </p>
                </div>

                {this.renderContinuation(isActive)}

                <dl className="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
                    <dt className="font-semibold">Status</dt>
                    <dd className="capitalize">
                        {isActive
                            ? "running"
                            : formatStatus(batch.ended_reason)}
                    </dd>
                    <dt className="font-semibold">Current Item</dt>
                    <dd>{this.renderItem(item)}</dd>
                    {batch.last_action ? (
                        <>
                            <dt className="font-semibold">Last Action</dt>
                            <dd>{batch.last_action}</dd>
                        </>
                    ) : null}
                    {batch.next_action ? (
                        <>
                            <dt className="font-semibold">Next Action</dt>
                            <dd>{batch.next_action}</dd>
                        </>
                    ) : null}
                    <dt className="font-semibold">Items Crafted</dt>
                    <dd>{formatNumber(batch.counts.crafted)}</dd>
                    {this.renderCurrencyDetails(batch)}
                    {set && movesToCraftedItemsSet ? (
                        <>
                            <dt className="font-semibold">Crafted Items Set</dt>
                            <dd>
                                {formatNumber(set.current_slots)} /{" "}
                                {formatNumber(set.max_slots)}
                            </dd>
                        </>
                    ) : null}
                </dl>

                {this.renderSkillsList(batch.skills)}

                {this.renderUsefulCounts(batch)}
                {this.renderCharts(batch)}
                {this.renderActionHistory(isActive, batch.ended_reason, true)}
                {this.renderActionButtons(isActive, isSaving)}
                {this.renderOpenModals()}
            </div>
        );
    }

    renderCraftSetPanel(
        batch: NonNullable<BatchCraftingStatus["batch"]>,
        isActive: boolean,
        isSaving: boolean,
    ) {
        const requested = batch.requested_amount ?? 0;
        const completed = batch.completed_amount ?? 0;
        const remaining =
            batch.remaining_amount ?? Math.max(0, requested - completed);
        const item =
            batch.craft_set_current_item ?? batch.current_item_snapshot;
        const outputDestinationDisplay =
            batch.output_destination === "inventory_set" && batch.output_set
                ? typeof batch.output_set.max_slots === "number"
                    ? `${batch.output_set.name} (${batch.output_set.current_slots} / ${batch.output_set.max_slots})`
                    : `${batch.output_set.name} (${batch.output_set.current_slots} used / unlimited)`
                : (batch.output_destination_label ?? "Crafted Items Set");

        return (
            <div className="space-y-4 text-sm" role="status" aria-live="polite">
                <div>
                    <h3 className="text-lg font-semibold">Craft Set</h3>
                    <p className="mt-1 text-gray-700 dark:text-gray-300">
                        {isActive
                            ? `Crafting set entries into ${outputDestinationDisplay}.`
                            : `Craft Set ended: ${formatStatus(batch.ended_reason)}.`}
                    </p>
                </div>

                {this.renderContinuation(isActive)}

                {this.renderCompletionSummary(
                    isActive,
                    batch.completion_summary,
                    {
                        all: "Crafted the full set.",
                        some: "Crafted a partial set. See the action history below for details.",
                        none: "Crafted none of the set. See the action history below for details.",
                    },
                )}

                <ProgressBar
                    label="Set Entries Completed"
                    current={completed}
                    max={requested}
                    percent={this.completionPercent(completed, requested)}
                    barClassName="bg-orange-600"
                />

                {this.renderOutputDestinationCapacity(batch)}

                <dl className="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
                    <dt className="font-semibold">Status</dt>
                    <dd>{this.statusText(batch, isActive)}</dd>
                    <dt className="font-semibold">Output Destination</dt>
                    <dd>{outputDestinationDisplay}</dd>
                    <dt className="font-semibold">Requested Entries</dt>
                    <dd>{formatNumber(requested)}</dd>
                    <dt className="font-semibold">Completed Entries</dt>
                    <dd>{formatNumber(completed)}</dd>
                    <dt className="font-semibold">Remaining Entries</dt>
                    <dd>{formatNumber(remaining)}</dd>
                    <dt className="font-semibold">Current Item</dt>
                    <dd>{this.renderItem(item)}</dd>
                    {this.renderCurrencyDetails(batch)}
                    <dt className="font-semibold">Skipped</dt>
                    <dd>{formatNumber(batch.counts.skipped)}</dd>
                    <dt className="font-semibold">Failed</dt>
                    <dd>{formatNumber(batch.counts.failed)}</dd>
                </dl>

                {this.renderSkillsList(batch.skills)}

                {this.renderCharts(batch)}
                {this.renderActionHistory(isActive, batch.ended_reason)}
                {this.renderActionButtons(isActive, isSaving)}
                {this.renderOpenModals()}
            </div>
        );
    }

    renderCraftAndEnchantSetPanel(
        batch: NonNullable<BatchCraftingStatus["batch"]>,
        isActive: boolean,
        isSaving: boolean,
    ) {
        const requested = batch.craft_enchant_set_requested ?? 0;
        const completedFinal =
            batch.craft_enchant_set_completed_final_count ?? 0;
        const item =
            batch.craft_enchant_set_current_item ?? batch.current_item_snapshot;
        const phaseLabels: Record<string, string> = {
            crafting: "Crafting set",
            enchanting: "Enchanting set",
            replacement_crafting: "Crafting replacement",
        };
        const phaseLabel = isActive
            ? (phaseLabels[batch.craft_enchant_set_phase ?? ""] ??
              "Crafting set")
            : formatStatus(batch.ended_reason);
        const outputDestinationDisplay =
            batch.output_destination === "inventory_set" && batch.output_set
                ? typeof batch.output_set.max_slots === "number"
                    ? `${batch.output_set.name} (${batch.output_set.current_slots} / ${batch.output_set.max_slots})`
                    : `${batch.output_set.name} (${batch.output_set.current_slots} used / unlimited)`
                : (batch.output_destination_label ?? "Crafted Items Set");

        return (
            <div className="space-y-4 text-sm" role="status" aria-live="polite">
                <div>
                    <h3 className="text-lg font-semibold">
                        Craft and Enchant Set
                    </h3>
                    <p className="mt-1 text-gray-700 dark:text-gray-300">
                        {isActive
                            ? `Building and enchanting the full set into ${outputDestinationDisplay}.`
                            : `Craft and Enchant Set ended: ${formatStatus(batch.ended_reason)}.`}
                    </p>
                </div>

                {this.renderIntTooLowWarning(batch, isActive)}
                {this.renderIntPreemptiveInfo(batch, isActive)}
                {this.renderContinuation(isActive)}

                {this.renderCompletionSummary(
                    isActive,
                    batch.completion_summary,
                    {
                        all: "Crafted and enchanted the full set.",
                        some: "Crafted and enchanted a partial set. See the action history below for details.",
                        none: "Crafted and enchanted none of the set. See the action history below for details.",
                    },
                )}

                <ProgressBar
                    label="Set Items Completed"
                    current={completedFinal}
                    max={requested}
                    percent={this.completionPercent(completedFinal, requested)}
                    barClassName="bg-orange-600"
                />

                <dl className="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
                    <dt className="font-semibold">Status</dt>
                    <dd>{this.statusText(batch, isActive)}</dd>
                    <dt className="font-semibold">Phase</dt>
                    <dd className="capitalize">{phaseLabel}</dd>
                    <dt className="font-semibold">Output Destination</dt>
                    <dd>{outputDestinationDisplay}</dd>
                    <dt className="font-semibold">Full Set Requested</dt>
                    <dd>{formatNumber(requested)}</dd>
                    <dt className="font-semibold">Crafted</dt>
                    <dd>{formatNumber(batch.counts.crafted)}</dd>
                    <dt className="font-semibold">Prefix Enchants Applied</dt>
                    <dd>
                        {formatNumber(
                            batch.craft_enchant_set_prefix_applied_count ?? 0,
                        )}
                    </dd>
                    <dt className="font-semibold">Suffix Enchants Applied</dt>
                    <dd>
                        {formatNumber(
                            batch.craft_enchant_set_suffix_applied_count ?? 0,
                        )}
                    </dd>
                    <dt className="font-semibold">Completed Final Items</dt>
                    <dd>{formatNumber(completedFinal)}</dd>
                    <dt className="font-semibold">Current Item</dt>
                    <dd>{this.renderItem(item)}</dd>
                    <dt className="font-semibold">Current Prefix</dt>
                    <dd>
                        {batch.craft_enchant_set_current_prefix
                            ? this.renderAffixName(
                                  batch.craft_enchant_set_current_prefix,
                                  batch.craft_enchant_set_current_prefix_affix,
                              )
                            : "None"}
                    </dd>
                    <dt className="font-semibold">Current Suffix</dt>
                    <dd>
                        {batch.craft_enchant_set_current_suffix
                            ? this.renderAffixName(
                                  batch.craft_enchant_set_current_suffix,
                                  batch.craft_enchant_set_current_suffix_affix,
                              )
                            : "None"}
                    </dd>
                    {this.renderCurrencyDetails(batch)}
                    {this.renderGoldDustGainedIfApplicable(batch)}
                    {this.renderListingValueDtDd(batch)}
                    <dt className="font-semibold">Failed</dt>
                    <dd>{formatNumber(batch.counts.failed)}</dd>
                    <dt className="font-semibold">Skipped</dt>
                    <dd>{formatNumber(batch.counts.skipped)}</dd>
                </dl>

                {this.renderCharts(batch)}
                {this.renderActionHistory(isActive, batch.ended_reason)}
                {this.renderActionButtons(isActive, isSaving)}
                {this.renderOpenModals()}
            </div>
        );
    }

    renderEnchantSetPanel(
        batch: NonNullable<BatchCraftingStatus["batch"]>,
        isActive: boolean,
        isSaving: boolean,
    ) {
        const set = batch.selected_set;
        const eligibleTotal = batch.enchant_set_total ?? 0;
        const enchantedCount =
            batch.enchant_set_completed ?? batch.counts.enchanted ?? 0;
        const remainingCount = Math.max(0, eligibleTotal - enchantedCount);
        const skippedCount = batch.enchant_set_skipped ?? batch.counts.skipped;
        const item =
            batch.enchant_set_current_item ?? batch.current_item_snapshot;

        return (
            <div className="space-y-4 text-sm" role="status" aria-live="polite">
                <div>
                    <h3 className="text-lg font-semibold">Enchant Set</h3>
                    <p className="mt-1 text-gray-700 dark:text-gray-300">
                        {isActive
                            ? `Enchanting eligible items in ${set?.name ?? "the selected set"}.`
                            : `Enchant Set ended: ${formatStatus(batch.ended_reason)}.`}
                    </p>
                </div>

                {this.renderIntTooLowWarning(batch, isActive)}
                {this.renderContinuation(isActive)}

                <ProgressBar
                    label="Items Enchanted"
                    current={enchantedCount}
                    max={eligibleTotal}
                    percent={this.completionPercent(
                        enchantedCount,
                        eligibleTotal,
                    )}
                    barClassName="bg-orange-600"
                />

                <dl className="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
                    <dt className="font-semibold">Status</dt>
                    <dd>{this.statusText(batch, isActive)}</dd>
                    <dt className="font-semibold">Selected Set</dt>
                    <dd>
                        {set
                            ? `${set.name} (${set.current_slots} / ${set.max_slots})`
                            : "None"}
                    </dd>
                    <dt className="font-semibold">Eligible Items</dt>
                    <dd>{formatNumber(eligibleTotal)}</dd>
                    <dt className="font-semibold">Enchanted</dt>
                    <dd>{formatNumber(enchantedCount)}</dd>
                    <dt className="font-semibold">Remaining</dt>
                    <dd>{formatNumber(remainingCount)}</dd>
                    <dt className="font-semibold">Skipped</dt>
                    <dd>{formatNumber(skippedCount)}</dd>
                    <dt className="font-semibold">Failed</dt>
                    <dd>{formatNumber(batch.counts.failed)}</dd>
                    <dt className="font-semibold">Selected Enchantments</dt>
                    <dd>
                        {this.renderAffixNamesList(
                            batch.enchant_affix_names,
                            batch.enchant_affix_ids,
                            batch.enchant_affixes,
                        )}
                    </dd>
                    <dt className="font-semibold">Current Item</dt>
                    <dd>{this.renderItem(item)}</dd>
                    <dt className="font-semibold">Gold Spent</dt>
                    <dd>{formatNumber(batch.gold_spent_total ?? 0)}</dd>
                    <dt className="font-semibold">Gold Left</dt>
                    <dd>{formatNumber(batch.gold_left ?? 0)}</dd>
                </dl>

                {this.renderCharts(batch)}
                {this.renderActionHistory(isActive, batch.ended_reason)}
                {this.renderActionButtons(isActive, isSaving)}
                {this.renderOpenModals()}
            </div>
        );
    }

    renderHolyOilItemsPreviewList(
        items: {
            item: BatchCraftingItemSnapshot | null;
            target_slot_id: number;
            current_stacks: number;
            planned_applications: number;
            resulting_stacks: number;
            maximum_stacks: number;
            exact_gold_dust_cost: number;
        }[],
    ) {
        if (items.length === 0) {
            return null;
        }

        return (
            <ul className="grid gap-2">
                {items.map((entry) => (
                    <li
                        key={entry.target_slot_id}
                        className="rounded border border-gray-300 p-3 dark:border-gray-600"
                    >
                        <dl className="grid grid-cols-2 gap-1 text-xs">
                            <dt className="font-semibold">Item</dt>
                            <dd>{this.renderItem(entry.item)}</dd>
                            <dt className="font-semibold">Stacks</dt>
                            <dd>
                                {formatNumber(entry.current_stacks)} /{" "}
                                {formatNumber(entry.maximum_stacks)}
                            </dd>
                            <dt className="font-semibold">Applications</dt>
                            <dd>{formatNumber(entry.planned_applications)}</dd>
                            <dt className="font-semibold">Resulting Stacks</dt>
                            <dd>
                                {formatNumber(entry.resulting_stacks)} /{" "}
                                {formatNumber(entry.maximum_stacks)}
                            </dd>
                            <dt className="font-semibold">
                                Exact Gold Dust Cost
                            </dt>
                            <dd>{formatNumber(entry.exact_gold_dust_cost)}</dd>
                        </dl>
                        <div className="mt-3">
                            <ProgressBar
                                label="Holy Oil Stacks"
                                current={entry.resulting_stacks}
                                max={entry.maximum_stacks}
                                percent={this.completionPercent(
                                    entry.resulting_stacks,
                                    entry.maximum_stacks,
                                )}
                            />
                        </div>
                    </li>
                ))}
            </ul>
        );
    }

    renderHolyOilsSelectedPreview(
        batch: NonNullable<BatchCraftingStatus["batch"]>,
    ) {
        const preview = batch.holy_oil_selected_preview;

        if (!preview) {
            return null;
        }

        return (
            <div className="grid gap-3">
                {this.renderHolyOilItemsPreviewList(preview.items)}
                <dl className="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
                    <dt className="font-semibold">Applications Planned</dt>
                    <dd>{formatNumber(preview.applications_planned)}</dd>
                    <dt className="font-semibold">Items Affected</dt>
                    <dd>{formatNumber(preview.items_affected)}</dd>
                    <dt className="font-semibold">Gold Dust Required</dt>
                    <dd>{formatNumber(preview.exact_gold_dust_required)}</dd>
                </dl>
                {preview.capped ? (
                    <WarningAlert>
                        {formatNumber(preview.oils_not_applicable)} selected
                        Holy Oils cannot be used. {preview.unapplied_reason}
                    </WarningAlert>
                ) : null}
            </div>
        );
    }

    renderHolyOilsSetPreview(batch: NonNullable<BatchCraftingStatus["batch"]>) {
        const preview = batch.holy_oil_set_preview;

        if (!preview) {
            return null;
        }

        return (
            <div className="grid gap-3">
                {this.renderHolyOilItemsPreviewList(preview.items)}
                <dl className="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
                    <dt className="font-semibold">Applications Planned</dt>
                    <dd>{formatNumber(preview.applications_planned)}</dd>
                    <dt className="font-semibold">Items Affected</dt>
                    <dd>{formatNumber(preview.items_affected)}</dd>
                    <dt className="font-semibold">Gold Dust Required</dt>
                    <dd>{formatNumber(preview.exact_gold_dust_required)}</dd>
                </dl>
                {preview.capped ? (
                    <WarningAlert>
                        {formatNumber(preview.oils_not_applicable)} selected
                        Holy Oils cannot be used. {preview.unapplied_reason}
                    </WarningAlert>
                ) : null}
            </div>
        );
    }

    renderHolyOilApplicationResults(
        batch: NonNullable<BatchCraftingStatus["batch"]>,
    ) {
        const results = batch.holy_oil_application_results ?? [];

        if (results.length === 0) {
            return null;
        }

        return (
            <div className="grid gap-3">
                <h4 className="font-semibold">Holy Oil Results</h4>
                <ul className="grid gap-2">
                    {results.map((result) => (
                        <li
                            key={result.target_slot_id}
                            className="rounded border border-gray-300 p-3 dark:border-gray-600"
                        >
                            <div className="mb-2 w-full">
                                {this.renderItem(result.item)}
                            </div>
                            <dl className="grid grid-cols-2 gap-1 text-xs">
                                <dt className="font-semibold">
                                    Applications Completed
                                </dt>
                                <dd>
                                    {formatNumber(
                                        result.actual_applications_completed,
                                    )}
                                </dd>
                                <dt className="font-semibold">Actual Stacks</dt>
                                <dd>
                                    {formatNumber(
                                        result.actual_resulting_stack_count,
                                    )}{" "}
                                    / {formatNumber(result.maximum_stacks)}
                                </dd>
                                <dt className="font-semibold">
                                    Maximum Stacks
                                </dt>
                                <dd>{formatNumber(result.maximum_stacks)}</dd>
                                <dt className="font-semibold">
                                    Gold Dust Spent
                                </dt>
                                <dd>
                                    {formatNumber(
                                        result.actual_gold_dust_spent,
                                    )}
                                </dd>
                                <dt className="font-semibold">
                                    Oil Applications Consumed
                                </dt>
                                <dd>
                                    {formatNumber(
                                        result.oil_applications_consumed,
                                    )}
                                </dd>
                            </dl>
                            <div className="mt-3">
                                <ProgressBar
                                    label="Holy Oil Stacks"
                                    current={
                                        result.actual_resulting_stack_count
                                    }
                                    max={result.maximum_stacks}
                                    percent={this.completionPercent(
                                        result.actual_resulting_stack_count,
                                        result.maximum_stacks,
                                    )}
                                />
                            </div>
                        </li>
                    ))}
                </ul>
            </div>
        );
    }

    renderHolyOilsSetPanel(
        batch: NonNullable<BatchCraftingStatus["batch"]>,
        isActive: boolean,
        isSaving: boolean,
    ) {
        const set = batch.selected_set;
        const totalStacks = batch.holy_oil_total_stacks ?? 0;
        const requested = batch.holy_oil_requested_applications ?? 0;
        const completed = batch.holy_oil_completed_applications ?? 0;
        const remaining =
            batch.holy_oil_remaining_applications ??
            Math.max(0, requested - completed);

        return (
            <div className="space-y-4 text-sm" role="status" aria-live="polite">
                <div>
                    <h3 className="text-lg font-semibold">Holy Oils Set</h3>
                    <p className="mt-1 text-gray-700 dark:text-gray-300">
                        {isActive
                            ? `Applying holy oils to eligible items in ${set?.name ?? "the selected set"}.`
                            : `Holy Oils Set ended: ${formatStatus(batch.ended_reason)}.`}
                    </p>
                </div>

                {this.renderContinuation(isActive)}

                <ProgressBar
                    label="Oils Applied"
                    current={completed}
                    max={requested}
                    percent={this.completionPercent(completed, requested)}
                    barClassName="bg-orange-600"
                />

                <dl className="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
                    <dt className="font-semibold">Status</dt>
                    <dd>{this.statusText(batch, isActive)}</dd>
                    <dt className="font-semibold">Selected Set</dt>
                    <dd>
                        {set
                            ? `${set.name} (${set.current_slots} / ${set.max_slots})`
                            : "None"}
                    </dd>
                    <dt className="font-semibold">Total Eligible Set Items</dt>
                    <dd>{formatNumber(batch.holy_oil_eligible_items ?? 0)}</dd>
                    <dt className="font-semibold">Total Holy Stacks</dt>
                    <dd>{formatNumber(totalStacks)}</dd>
                    <dt className="font-semibold">Oils Needed</dt>
                    <dd>{formatNumber(requested)}</dd>
                    <dt className="font-semibold">Oils Applied</dt>
                    <dd>{formatNumber(completed)}</dd>
                    <dt className="font-semibold">Oils Remaining</dt>
                    <dd>{formatNumber(remaining)}</dd>
                    <dt className="font-semibold">Skipped/Ineligible</dt>
                    <dd>{formatNumber(batch.holy_oil_skipped_items ?? 0)}</dd>
                    <dt className="font-semibold">Total Stat Bonus Applied</dt>
                    <dd>
                        {(batch.holy_oil_total_stat_bonus_applied ?? 0).toFixed(
                            2,
                        )}
                    </dd>
                    <dt className="font-semibold">
                        Total Devouring Darkness Bonus Applied
                    </dt>
                    <dd>
                        {(
                            batch.holy_oil_total_devouring_darkness_bonus_applied ??
                            0
                        ).toFixed(2)}
                    </dd>
                    <dt className="font-semibold">Current Target Item</dt>
                    <dd>
                        {this.renderItem(batch.holy_oil_current_target_item)}
                    </dd>
                    <dt className="font-semibold">Current Oil Item</dt>
                    <dd>{this.renderItem(batch.holy_oil_current_oil_item)}</dd>
                    <dt className="font-semibold">Gold Dust Spent</dt>
                    <dd>{formatNumber(batch.holy_oil_gold_dust_spent ?? 0)}</dd>
                    <dt className="font-semibold">Gold Dust Left</dt>
                    <dd>{formatNumber(batch.gold_dust_left ?? 0)}</dd>
                    {(batch.gold_gained_total ?? 0) > 0 ? (
                        <>
                            <dt className="font-semibold">Gold Gained</dt>
                            <dd>
                                {formatNumber(batch.gold_gained_total ?? 0)}
                            </dd>
                        </>
                    ) : null}
                    {this.renderListingValueDtDd(batch)}
                </dl>

                {this.renderHolyOilApplicationResults(batch)}

                {this.renderCharts(batch)}
                {this.renderActionHistory(isActive, batch.ended_reason)}
                {this.renderActionButtons(isActive, isSaving)}
                {this.renderOpenModals()}
            </div>
        );
    }

    renderCraftAndEnchantExperiencePanel(
        batch: NonNullable<BatchCraftingStatus["batch"]>,
        isActive: boolean,
        isSaving: boolean,
    ) {
        const disenchants = [
            "disenchant",
            "keep_best_disenchant_rest",
        ].includes(batch.disposition);
        const sells = ["sell", "keep_best_sell_rest"].includes(
            batch.disposition,
        );
        const set = batch.batch_crafting_set;
        const movesToCraftedItemsSet = [
            "keep",
            "keep_highest",
            "keep_best_sell_rest",
            "keep_best_destroy_rest",
            "keep_best_disenchant_rest",
        ].includes(batch.disposition);

        return (
            <div className="space-y-4 text-sm" role="status" aria-live="polite">
                <h3 className="text-lg font-semibold">
                    Craft and Enchant for Experience
                </h3>
                {this.renderExperienceRateLabel(batch)}
                {this.renderIntTooLowWarning(batch, isActive)}
                {this.renderIntPreemptiveInfo(batch, isActive)}
                {this.renderContinuation(isActive)}
                {this.renderDetailGrid([
                    {
                        label: "Status",
                        value: this.statusText(batch, isActive),
                    },
                    {
                        label: "Current Crafted Item",
                        value: this.renderItem(
                            batch.current_crafted_item_snapshot ??
                                batch.current_item_snapshot,
                        ),
                    },
                    {
                        label: "Current Enchanted Item",
                        value: this.renderItem(
                            batch.current_enchanted_item_snapshot,
                        ),
                    },
                    {
                        label: "Gold Spent",
                        value: formatNumber(batch.gold_spent_total ?? 0),
                    },
                    {
                        label: "Gold Left",
                        value: formatNumber(batch.gold_left ?? 0),
                    },
                    {
                        label: "Gold Dust Gained",
                        value: formatNumber(batch.gold_dust_gained_total ?? 0),
                        show: disenchants,
                    },
                    {
                        label: "Gold Gained",
                        value: formatNumber(batch.gold_gained_total ?? 0),
                        show: sells,
                    },
                    {
                        label: "Successes",
                        value: formatNumber(
                            (batch.counts.crafted ?? 0) +
                                (batch.counts.enchanted ?? 0),
                        ),
                    },
                    {
                        label: "Failures",
                        value: formatNumber(batch.counts.failed),
                    },
                    {
                        label: "Crafted Items Set",
                        value: set
                            ? `${formatNumber(set.current_slots)} / ${formatNumber(set.max_slots)}`
                            : "0 / 2000",
                        show: !!set && movesToCraftedItemsSet,
                    },
                    ...this.listingSummaryRows(batch),
                ])}
                {this.renderSkillsList(batch.skills)}
                {this.renderUsefulCounts(batch)}
                {this.renderCharts(batch)}
                {this.renderActionHistory(isActive, batch.ended_reason)}
                {this.renderActionButtons(isActive, isSaving)}
                {this.renderOpenModals()}
            </div>
        );
    }

    renderCraftAndEnchantSpecificPanel(
        batch: NonNullable<BatchCraftingStatus["batch"]>,
        isActive: boolean,
        isSaving: boolean,
    ) {
        const requested = batch.requested_amount ?? 0;
        const completed = batch.completed_amount ?? 0;
        const remaining =
            batch.remaining_amount ?? Math.max(0, requested - completed);

        return (
            <div className="space-y-4 text-sm" role="status" aria-live="polite">
                <h3 className="text-lg font-semibold">
                    Craft and Enchant Amount
                </h3>
                {this.renderIntTooLowWarning(batch, isActive)}
                {this.renderIntPreemptiveInfo(batch, isActive)}
                {this.renderContinuation(isActive)}
                <ProgressBar
                    label="Batch Progress"
                    current={completed}
                    max={requested}
                    percent={this.completionPercent(completed, requested)}
                    barClassName="bg-orange-600"
                />
                {this.renderOutputDestinationCapacity(batch)}
                {this.renderAmountPreview(batch)}
                {this.renderDetailGrid([
                    {
                        label: "Status",
                        value: this.statusText(batch, isActive),
                    },
                    {
                        label: "Selected Craft Item",
                        value: this.renderItem(batch.current_item_snapshot),
                    },
                    {
                        label: "Selected Enchantments",
                        value: this.renderAffixNamesList(
                            batch.enchant_affix_names,
                            batch.enchant_affix_ids,
                            batch.enchant_affixes,
                        ),
                    },
                    { label: "Requested", value: formatNumber(requested) },
                    { label: "Enchanted", value: formatNumber(completed) },
                    { label: "Remaining", value: formatNumber(remaining) },
                    {
                        label: "Gold Spent",
                        value: formatNumber(batch.gold_spent_total ?? 0),
                    },
                    {
                        label: "Gold Left",
                        value: formatNumber(batch.gold_left ?? 0),
                    },
                    {
                        label: "Gold Dust Gained",
                        value: formatNumber(batch.gold_dust_gained_total ?? 0),
                        show: [
                            "disenchant",
                            "keep_best_disenchant_rest",
                        ].includes(batch.disposition),
                    },
                    {
                        label: "Gold Gained",
                        value: formatNumber(batch.gold_gained_total ?? 0),
                        show: ["sell", "keep_best_sell_rest"].includes(
                            batch.disposition,
                        ),
                    },
                    {
                        label: "Failed",
                        value: formatNumber(batch.counts.failed),
                    },
                    {
                        label: "Skipped",
                        value: formatNumber(batch.counts.skipped),
                    },
                    ...this.listingSummaryRows(batch),
                ])}
                {this.renderCharts(batch)}
                {this.renderActionHistory(isActive, batch.ended_reason)}
                {this.renderActionButtons(isActive, isSaving)}
                {this.renderOpenModals()}
            </div>
        );
    }

    renderEventPanel(
        batch: NonNullable<BatchCraftingStatus["batch"]>,
        isActive: boolean,
        isSaving: boolean,
        actionLabel: string,
    ) {
        const isEnchant = batch.event_action === "enchant";

        return (
            <div className="space-y-4 text-sm" role="status" aria-live="polite">
                <h3 className="text-lg font-semibold">{actionLabel}</h3>
                {this.renderExperienceRateLabel(batch)}
                {isEnchant
                    ? this.renderIntTooLowWarning(batch, isActive)
                    : null}
                {this.renderContinuation(isActive)}
                {this.renderDetailGrid([
                    {
                        label: "Status",
                        value: this.statusText(batch, isActive),
                    },
                    {
                        label: "Event",
                        value:
                            this.props.status.event_batch?.event_name ??
                            "Current event",
                    },
                    {
                        label: "Event Step",
                        value: formatStatus(
                            batch.event_step ??
                                this.props.status.event_batch?.current_step,
                        ),
                    },
                    {
                        label: isEnchant ? "Enchanted" : "Crafted",
                        value: formatNumber(
                            isEnchant
                                ? (batch.counts.enchanted ?? 0)
                                : batch.counts.crafted,
                        ),
                    },
                    {
                        label: "Event Goal Progress",
                        value: batch.event_goal_progress
                            ? `${formatNumber(batch.event_goal_progress.current)} / ${formatNumber(batch.event_goal_progress.max)}`
                            : "Unavailable",
                    },
                    {
                        label: "Character Contribution",
                        value: batch.event_character_contribution
                            ? `${formatNumber(batch.event_character_contribution.current)} / ${formatNumber(batch.event_character_contribution.reward_threshold)}`
                            : "Unavailable",
                    },
                    {
                        label: "Gold Spent",
                        value: formatNumber(batch.gold_spent_total ?? 0),
                    },
                    {
                        label: "Gold Left",
                        value: formatNumber(batch.gold_left ?? 0),
                    },
                    {
                        label: isEnchant
                            ? "Enchanting XP Gained"
                            : "Crafting XP Gained",
                        value: formatNumber(
                            isEnchant
                                ? (batch.event_enchanting_xp_gained ?? 0)
                                : (batch.event_crafting_xp_gained ?? 0),
                        ),
                    },
                    {
                        label: "Failures",
                        value: formatNumber(batch.counts.failed),
                    },
                    {
                        label: "Skips",
                        value: formatNumber(batch.counts.skipped),
                    },
                ])}
                {this.renderCharts(batch)}
                {this.renderActionHistory(isActive, batch.ended_reason)}
                {this.renderActionButtons(isActive, isSaving)}
                {this.renderOpenModals()}
            </div>
        );
    }

    renderAlchemyExperiencePanel(
        batch: NonNullable<BatchCraftingStatus["batch"]>,
        isActive: boolean,
        isSaving: boolean,
    ) {
        return (
            <div className="space-y-4 text-sm" role="status" aria-live="polite">
                <h3 className="text-lg font-semibold">
                    Alchemy for Experience
                </h3>
                {this.renderExperienceRateLabel(batch)}
                {this.renderContinuation(isActive)}
                {this.renderSkillsList(batch.skills)}
                <ProgressBar
                    label="Alchemy Bag Used"
                    current={batch.alchemy_bag_count}
                    max={batch.alchemy_bag_max}
                    percent={
                        batch.alchemy_bag_max > 0
                            ? Math.min(
                                  100,
                                  Math.floor(
                                      (batch.alchemy_bag_count /
                                          batch.alchemy_bag_max) *
                                          100,
                                  ),
                              )
                            : 0
                    }
                    barClassName="bg-regent-st-blue-500"
                />
                {this.renderDetailGrid([
                    {
                        label: "Status",
                        value: this.statusText(batch, isActive),
                    },
                    {
                        label: "Current Item",
                        value: this.renderItem(
                            batch.alchemy_current_item ??
                                batch.current_item_snapshot,
                        ),
                    },
                    {
                        label: "Gold Dust Spent",
                        value: formatNumber(batch.gold_dust_spent_total ?? 0),
                    },
                    {
                        label: "Gold Dust Left",
                        value: formatNumber(batch.gold_dust_left ?? 0),
                    },
                    {
                        label: "Gold Gained",
                        value: formatNumber(batch.gold_gained_total ?? 0),
                        show: (batch.gold_gained_total ?? 0) > 0,
                    },
                    {
                        label: "Successes",
                        value: formatNumber(batch.counts.crafted),
                    },
                    {
                        label: "Failures",
                        value: formatNumber(batch.counts.failed),
                    },
                    ...this.listingSummaryRows(batch),
                ])}
                {this.renderCharts(batch)}
                {this.renderActionHistory(isActive, batch.ended_reason)}
                {this.renderActionButtons(isActive, isSaving)}
                {this.renderOpenModals()}
            </div>
        );
    }

    renderAlchemyAmountPanel(
        batch: NonNullable<BatchCraftingStatus["batch"]>,
        isActive: boolean,
        isSaving: boolean,
    ) {
        const requested = batch.requested_amount ?? 0;
        const completed = batch.completed_amount ?? 0;
        const remaining =
            batch.remaining_amount ?? Math.max(0, requested - completed);

        return (
            <div className="space-y-4 text-sm" role="status" aria-live="polite">
                <h3 className="text-lg font-semibold">Alchemy Amount</h3>
                {this.renderContinuation(isActive)}
                <ProgressBar
                    label="Batch Progress"
                    current={completed}
                    max={requested}
                    percent={this.completionPercent(completed, requested)}
                    barClassName="bg-orange-600"
                />
                {this.renderDetailGrid([
                    {
                        label: "Status",
                        value: this.statusText(batch, isActive),
                    },
                    {
                        label: "Selected Alchemy Item",
                        value: this.renderItem(
                            batch.alchemy_current_item ??
                                batch.current_item_snapshot,
                        ),
                    },
                    { label: "Requested", value: formatNumber(requested) },
                    { label: "Completed", value: formatNumber(completed) },
                    { label: "Remaining", value: formatNumber(remaining) },
                    {
                        label: "Alchemy Bag",
                        value: `${formatNumber(batch.alchemy_bag_count)} / ${formatNumber(batch.alchemy_bag_max)}`,
                    },
                    {
                        label: "Gold Dust Spent",
                        value: formatNumber(batch.gold_dust_spent_total ?? 0),
                    },
                    {
                        label: "Gold Dust Left",
                        value: formatNumber(batch.gold_dust_left ?? 0),
                    },
                    {
                        label: "Shards Spent",
                        value: formatNumber(batch.shards_spent_total ?? 0),
                        show: (batch.shards_spent_total ?? 0) > 0,
                    },
                    {
                        label: "Gold Gained",
                        value: formatNumber(batch.gold_gained_total ?? 0),
                        show: (batch.gold_gained_total ?? 0) > 0,
                    },
                    ...this.listingSummaryRows(batch),
                ])}
                {this.renderAlchemyAmountPreview(batch)}
                {this.renderCharts(batch)}
                {this.renderActionHistory(isActive, batch.ended_reason)}
                {this.renderActionButtons(isActive, isSaving)}
                {this.renderOpenModals()}
            </div>
        );
    }

    renderAlchemyAmountPreview(
        batch: NonNullable<BatchCraftingStatus["batch"]>,
    ) {
        const preview = batch.alchemy_amount_preview;

        if (!preview) {
            return null;
        }

        return (
            <div className="grid gap-3">
                <dl className="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
                    <dt className="font-semibold">Selected Item</dt>
                    <dd>{this.renderItem(preview.selected_item)}</dd>
                    <dt className="font-semibold">Per Item Cost</dt>
                    <dd>
                        {formatNumber(preview.gold_dust_cost_per_item)} gold
                        dust
                        {preview.shards_cost_per_item > 0
                            ? `, ${formatNumber(preview.shards_cost_per_item)} shards`
                            : ""}
                    </dd>
                    <dt className="font-semibold">Total Cost</dt>
                    <dd>
                        {formatNumber(preview.total_gold_dust_cost)} gold dust
                        {preview.total_shards_cost > 0
                            ? `, ${formatNumber(preview.total_shards_cost)} shards`
                            : ""}
                    </dd>
                    <dt className="font-semibold">Available Currency</dt>
                    <dd>
                        {formatNumber(preview.available_gold_dust)} gold dust,{" "}
                        {formatNumber(preview.available_shards)} shards
                    </dd>
                    <dt className="font-semibold">Alchemy Bag Space</dt>
                    <dd>
                        {formatNumber(preview.bag_current)} /{" "}
                        {formatNumber(preview.bag_max)} (
                        {formatNumber(preview.bag_remaining)} remaining)
                    </dd>
                    <dt className="font-semibold">
                        Effective Craftable Amount
                    </dt>
                    <dd>
                        {formatNumber(preview.effective_craftable_amount)} of{" "}
                        {formatNumber(preview.remaining_requested_amount)}
                    </dd>
                </dl>
                {preview.capped ? (
                    <WarningAlert>
                        This batch can only complete{" "}
                        {formatNumber(preview.effective_craftable_amount)} of
                        the requested{" "}
                        {formatNumber(preview.remaining_requested_amount)} items
                        with your current currency and alchemy bag space.
                    </WarningAlert>
                ) : null}
            </div>
        );
    }

    renderTrinketryExperiencePanel(
        batch: NonNullable<BatchCraftingStatus["batch"]>,
        isActive: boolean,
        isSaving: boolean,
    ) {
        return (
            <div className="space-y-4 text-sm" role="status" aria-live="polite">
                <h3 className="text-lg font-semibold">
                    Trinketry for Experience
                </h3>
                {this.renderExperienceRateLabel(batch)}
                {this.renderContinuation(isActive)}
                {this.renderSkillsList(batch.skills)}
                {this.renderDetailGrid([
                    {
                        label: "Status",
                        value: this.statusText(batch, isActive),
                    },
                    {
                        label: "Current Item",
                        value: this.renderItem(
                            batch.trinketry_current_item ??
                                batch.current_item_snapshot,
                        ),
                    },
                    {
                        label: "Shards Spent",
                        value: formatNumber(batch.shards_spent_total ?? 0),
                    },
                    {
                        label: "Shards Left",
                        value: formatNumber(batch.currency?.amount ?? 0),
                    },
                    {
                        label: "Gold Gained",
                        value: formatNumber(batch.gold_gained_total ?? 0),
                        show: (batch.gold_gained_total ?? 0) > 0,
                    },
                    {
                        label: "Successes",
                        value: formatNumber(batch.counts.crafted),
                    },
                    {
                        label: "Failures",
                        value: formatNumber(batch.counts.failed),
                    },
                ])}
                {this.renderCharts(batch)}
                {this.renderActionHistory(isActive, batch.ended_reason)}
                {this.renderActionButtons(isActive, isSaving)}
                {this.renderOpenModals()}
            </div>
        );
    }

    renderHolyOilsSelectedPanel(
        batch: NonNullable<BatchCraftingStatus["batch"]>,
        isActive: boolean,
        isSaving: boolean,
    ) {
        const requested =
            batch.holy_oil_requested_applications ??
            batch.requested_amount ??
            0;
        const completed =
            batch.holy_oil_completed_applications ??
            batch.completed_amount ??
            0;
        const remaining =
            batch.holy_oil_remaining_applications ??
            Math.max(0, requested - completed);

        return (
            <div className="space-y-4 text-sm" role="status" aria-live="polite">
                <h3 className="text-lg font-semibold">
                    Holy Oils Selected Gear
                </h3>
                {this.renderContinuation(isActive)}
                <ProgressBar
                    label="Oils Applied"
                    current={completed}
                    max={requested}
                    percent={this.completionPercent(completed, requested)}
                    barClassName="bg-orange-600"
                />
                {this.renderDetailGrid([
                    {
                        label: "Status",
                        value: this.statusText(batch, isActive),
                    },
                    {
                        label: "Selected Items",
                        value: formatNumber(
                            batch.holy_oil_selected_item_count ?? 0,
                        ),
                    },
                    {
                        label: "Selected Oils",
                        value: formatNumber(
                            batch.holy_oil_selected_oil_count ?? 0,
                        ),
                    },
                    {
                        label: "Requested Applications",
                        value: formatNumber(requested),
                    },
                    {
                        label: "Completed Applications",
                        value: formatNumber(completed),
                    },
                    {
                        label: "Remaining Applications",
                        value: formatNumber(remaining),
                    },
                    {
                        label: "Gold Dust Spent",
                        value: formatNumber(
                            batch.holy_oil_gold_dust_spent ??
                                batch.gold_dust_spent_total ??
                                0,
                        ),
                    },
                    {
                        label: "Gold Dust Left",
                        value: formatNumber(batch.gold_dust_left ?? 0),
                    },
                    {
                        label: "Total Stat Bonus Applied",
                        value: (
                            batch.holy_oil_total_stat_bonus_applied ?? 0
                        ).toFixed(2),
                    },
                    {
                        label: "Total Devouring Darkness Bonus Applied",
                        value: (
                            batch.holy_oil_total_devouring_darkness_bonus_applied ??
                            0
                        ).toFixed(2),
                    },
                    {
                        label: "Current Target Item",
                        value: this.renderItem(
                            batch.holy_oil_current_target_item,
                        ),
                    },
                    {
                        label: "Current Oil Item",
                        value: this.renderItem(batch.holy_oil_current_oil_item),
                    },
                    {
                        label: "Gold Gained",
                        value: formatNumber(batch.gold_gained_total ?? 0),
                        show: (batch.gold_gained_total ?? 0) > 0,
                    },
                    ...this.listingSummaryRows(batch),
                ])}
                {this.renderHolyOilApplicationResults(batch)}
                {this.renderCharts(batch)}
                {this.renderActionHistory(isActive, batch.ended_reason)}
                {this.renderActionButtons(isActive, isSaving)}
                {this.renderOpenModals()}
            </div>
        );
    }

    renderOpenModals() {
        const { character_id } = this.props;

        return (
            <React.Fragment>
                {this.state.openItemId !== null ? (
                    <InventoryUseDetails
                        is_open={true}
                        character_id={character_id}
                        item_id={this.state.openItemId}
                        slot_id={this.state.openSetSlotId ?? undefined}
                        manage_modal={() => this.setOpenItemId(null)}
                    />
                ) : null}
                {this.state.openSnapshot !== null &&
                this.state.openSnapshot.full_item_details ? (
                    <Dialogue
                        is_open={true}
                        handle_close={() => this.setOpenSnapshot(null)}
                        title={
                            <ItemNameColorationText
                                custom_width={false}
                                item={itemForColor(this.state.openSnapshot)}
                            />
                        }
                        large_modal={true}
                        additional_dialogue_css={"top-[110px]"}
                    >
                        <ItemDetails
                            item={this.state.openSnapshot.full_item_details}
                            character_id={character_id}
                        />
                    </Dialogue>
                ) : null}
                {this.state.affixDetailsModalAffix ? (
                    <ItemAffixDetails
                        is_open={this.state.affixDetailsModalOpen}
                        affix={this.state.affixDetailsModalAffix}
                        manage_modal={() => this.closeAffixDetails()}
                    />
                ) : null}
            </React.Fragment>
        );
    }

    render() {
        const { isSaving, status } = this.props;
        const batch = status.batch;

        if (!batch) {
            return null;
        }

        const isActive = status.active;

        if (batch.batch_type === "craft" && batch.mode === "specific_item") {
            return this.renderSpecificCraftingPanel(batch, isActive, isSaving);
        }

        if (batch.batch_type === "craft" && batch.mode === "experience") {
            return this.renderCraftExperiencePanel(batch, isActive, isSaving);
        }

        if (batch.batch_type === "craft" && batch.mode === "craft_set") {
            return this.renderCraftSetPanel(batch, isActive, isSaving);
        }

        if (batch.batch_type === "craft" && batch.mode === "event") {
            return this.renderEventPanel(
                batch,
                isActive,
                isSaving,
                "Craft for Event",
            );
        }

        if (
            batch.batch_type === "craft_and_enchant" &&
            batch.mode === "experience"
        ) {
            return this.renderCraftAndEnchantExperiencePanel(
                batch,
                isActive,
                isSaving,
            );
        }

        if (
            batch.batch_type === "craft_and_enchant" &&
            batch.mode === "specific_item"
        ) {
            return this.renderCraftAndEnchantSpecificPanel(
                batch,
                isActive,
                isSaving,
            );
        }

        if (
            batch.batch_type === "craft_and_enchant" &&
            batch.mode === "craft_enchant_set"
        ) {
            return this.renderCraftAndEnchantSetPanel(
                batch,
                isActive,
                isSaving,
            );
        }

        if (batch.batch_type === "enchant" && batch.mode === "event") {
            return this.renderEventPanel(
                batch,
                isActive,
                isSaving,
                "Enchant for Event",
            );
        }

        if (batch.batch_type === "enchant" && batch.mode === "set") {
            return this.renderEnchantSetPanel(batch, isActive, isSaving);
        }

        if (batch.batch_type === "alchemy" && batch.mode === "experience") {
            return this.renderAlchemyExperiencePanel(batch, isActive, isSaving);
        }

        if (batch.batch_type === "alchemy" && batch.mode === "amount") {
            return this.renderAlchemyAmountPanel(batch, isActive, isSaving);
        }

        if (batch.batch_type === "trinketry") {
            return this.renderTrinketryExperiencePanel(
                batch,
                isActive,
                isSaving,
            );
        }

        if (batch.batch_type === "holy_oils" && batch.mode === "selected") {
            return this.renderHolyOilsSelectedPanel(batch, isActive, isSaving);
        }

        if (batch.batch_type === "holy_oils" && batch.mode === "set") {
            return this.renderHolyOilsSetPanel(batch, isActive, isSaving);
        }

        return (
            <div
                className="space-y-4 text-sm"
                role="status"
                aria-live="polite"
                aria-label={
                    isActive ? "Batch crafting running" : "Batch crafting ended"
                }
            >
                <h3 className="text-lg font-semibold">Batch Crafting</h3>
                {this.renderDetailGrid([
                    { label: "Type", value: batch.batch_label },
                    {
                        label: "Status",
                        value: isActive
                            ? "Running"
                            : formatStatus(batch.ended_reason),
                    },
                    {
                        label: "Currency",
                        value: `${formatNumber(batch.currency.amount)} ${batch.currency.type}`,
                    },
                    {
                        label: "Current Item",
                        value: this.renderItem(batch.current_item_snapshot),
                    },
                ])}
                {this.renderContinuation(isActive)}
                <ProgressBar
                    label="Batch Progress"
                    current={batch.completed_amount ?? 0}
                    max={batch.requested_amount ?? 100}
                    percent={this.completionPercent(
                        batch.completed_amount ?? 0,
                        batch.requested_amount ?? 100,
                    )}
                    barClassName="bg-orange-600"
                />
                {this.renderUsefulCounts(batch)}
                {this.renderActionHistory(isActive, batch.ended_reason)}
                {this.renderActionButtons(isActive, isSaving)}
                {this.renderOpenModals()}
            </div>
        );
    }
}
