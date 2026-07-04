import React from "react";
import { AxisOptions, Chart } from "react-charts";
import DangerButton from "../../ui/buttons/danger-button";
import PrimaryButton from "../../ui/buttons/primary-button";
import ItemNameColorationText from "../../items/item-name/item-name-coloration-text";
import ItemDetailsModal from "../../modals/item-details/item-details-modal";
import InfoAlert from "../../ui/alerts/simple-alerts/info-alert";
import WarningAlert from "../../ui/alerts/simple-alerts/warning-alert";
import BatchCraftingStatusDisplayProps from "./types/batch-crafting-status-display-props";
import BatchCraftingStatusDisplayState from "./types/batch-crafting-status-display-state";
import { formatLocalDateTime } from "../../../lib/game/format-local-date";
import { formatNumber } from "../../../lib/game/format-number";

type ChartPoint = { label: string; value: number };
type ChartLine = { label: string; data: ChartPoint[]; color?: string };
type BatchCraftingSnapshotListItem = {
    display_name: string;
    quantity: number;
    snapshot: BatchCraftingItemSnapshot;
    slot_id: number | null;
    crafted_at: string | null;
};

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
            const color = lines[series.index]?.color;

            return color ? { fill: color, stroke: color } : {};
        },
        [lines],
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
                                data: lines,
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
};

export type BatchCraftingActionLogEntry = {
    ts: string;
    timestamp?: string;
    action_type?: string;
    status?: string;
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
    prefix_applied?: boolean;
    suffix_applied?: boolean;
    destination_set?: string;
    moved_to_set?: boolean;
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
        crafted_item_snapshots?: BatchCraftingSnapshotListItem[];
        enchanted_item_snapshots?: BatchCraftingSnapshotListItem[];
        alchemy_item_snapshots?: BatchCraftingSnapshotListItem[];
        trinketry_item_snapshots?: BatchCraftingSnapshotListItem[];
        holy_oil_target_item_snapshots?: BatchCraftingSnapshotListItem[];
        gold_spent?: number;
        gold_spent_total?: number;
        gold_gained_total?: number;
        gold_left?: number;
        gold_dust_spent_total?: number;
        gold_dust_gained_total?: number;
        gold_dust_left?: number;
        craft_set_current_item?: BatchCraftingItemSnapshot | null;
        craft_enchant_set_phase?:
            | "crafting"
            | "enchanting"
            | "finalizing"
            | null;
        craft_enchant_set_requested?: number | null;
        craft_enchant_set_prefix_applied_count?: number | null;
        craft_enchant_set_suffix_applied_count?: number | null;
        craft_enchant_set_completed_final_count?: number | null;
        craft_enchant_set_current_item?: BatchCraftingItemSnapshot | null;
        craft_enchant_set_current_prefix?: string | null;
        craft_enchant_set_current_suffix?: string | null;
        enchant_set_total?: number | null;
        enchant_set_completed?: number | null;
        enchant_set_skipped?: number | null;
        enchant_set_current_item?: BatchCraftingItemSnapshot | null;
        enchant_affix_ids?: number[] | null;
        enchant_affix_names?: string[];
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
        chart_points?: {
            currency: { tick: number; spent: number; gained: number }[];
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
            destination: string;
            destination_current_slots: number;
            destination_max_slots: number;
            destination_remaining_slots: number;
            effective_craftable_amount: number;
            capped: boolean;
        } | null;
        holy_oil_selected_preview?: {
            items: {
                item: BatchCraftingItemSnapshot | null;
                current_stacks: number;
                max_stacks: number;
                remaining_capacity: number;
                gold_dust_cost_per_application: number;
            }[];
            total_eligible_items: number;
            total_remaining_applications: number;
            selected_oils_available: number;
            gold_dust_available: number;
            total_cost_if_fully_applied: number;
            max_applications_possible: number;
            capped: boolean;
        } | null;
        holy_oil_set_preview?: {
            set_name: string;
            items: {
                item: BatchCraftingItemSnapshot | null;
                current_stacks: number;
                max_stacks: number;
                remaining_capacity: number;
                gold_dust_cost_per_application: number;
            }[];
            total_eligible_items: number;
            total_remaining_applications: number;
            selected_oils_available: number;
            gold_dust_available: number;
            total_cost_if_fully_applied: number;
            max_applications_possible: number;
            capped: boolean;
            capped_message: string | null;
        } | null;
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

function ProgressBar({
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

function SnapshotDetailsModal({
    item,
    onClose,
}: {
    item: BatchCraftingItemSnapshot;
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

    const rows = [
        { label: "Type", value: item.type },
        { label: "Base Damage", value: item.base_damage },
        { label: "Base AC", value: item.base_ac },
        { label: "Base Healing", value: item.base_healing },
        { label: "STR", value: item.str_modifier },
        { label: "DEX", value: item.dex_modifier },
        { label: "AGI", value: item.agi_modifier },
        { label: "CHR", value: item.chr_modifier },
        { label: "DUR", value: item.dur_modifier },
        { label: "INT", value: item.int_modifier },
        { label: "FOCUS", value: item.focus_modifier },
    ].filter((row) => {
        if (typeof row.value === "number") {
            return row.value > 0;
        }

        return row.value !== null && typeof row.value !== "undefined";
    });

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="batch-item-snapshot-title"
        >
            <div className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-sm bg-white p-6 shadow-lg dark:bg-gray-800 dark:text-gray-100">
                <div className="flex items-start justify-between gap-4">
                    <h2
                        id="batch-item-snapshot-title"
                        className="text-xl font-semibold"
                    >
                        {item.name ?? "Item Details"}
                    </h2>
                    <button
                        type="button"
                        className="rounded-sm border border-gray-300 px-3 py-1 text-sm font-semibold dark:border-gray-600"
                        onClick={onClose}
                    >
                        Close
                    </button>
                </div>
                {item.description ? (
                    <p className="mt-3 text-sm text-gray-700 dark:text-gray-300">
                        {item.description}
                    </p>
                ) : null}
                <dl className="mt-4 grid gap-3 sm:grid-cols-2">
                    {rows.map((row) => (
                        <div key={row.label}>
                            <dt className="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                {row.label}
                            </dt>
                            <dd>
                                {typeof row.value === "number"
                                    ? formatNumber(row.value)
                                    : row.value}
                            </dd>
                        </div>
                    ))}
                </dl>
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
            itemPages: {},
            openSlotId: null,
            openSnapshot: null,
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

    itemPage(pageKey: string, totalPages: number) {
        return Math.min(this.state.itemPages[pageKey] ?? 1, totalPages);
    }

    setItemPage(pageKey: string, page: number) {
        this.setState({
            itemPages: {
                ...this.state.itemPages,
                [pageKey]: page,
            },
        });
    }

    setOpenSlotId(openSlotId: number | null) {
        this.setState({
            openSlotId,
        });
    }

    setOpenSnapshot(openSnapshot: BatchCraftingItemSnapshot | null) {
        this.setState({
            openSnapshot,
        });
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

        if (item.can_view && item.slot_id_for_modal) {
            return (
                <button
                    type="button"
                    className="text-left hover:underline focus:outline-none focus:ring-2 focus:ring-blue-500"
                    aria-label={`View details for ${item.name}`}
                    onClick={() =>
                        this.setOpenSlotId(item.slot_id_for_modal ?? null)
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
                    <div>
                        <dt className="font-semibold">Gold Dust Spent</dt>
                        <dd>
                            {formatNumber(batch.gold_dust_spent_total ?? 0)}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Gold Dust Gained</dt>
                        <dd>
                            {formatNumber(batch.gold_dust_gained_total ?? 0)}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Gold Dust Left</dt>
                        <dd>{formatNumber(batch.gold_dust_left ?? 0)}</dd>
                    </div>
                </>
            );
        }

        if (currencyType === "shards") {
            return (
                <div>
                    <dt className="font-semibold">Shards Left</dt>
                    <dd>{formatNumber(batch.currency?.amount ?? 0)}</dd>
                </div>
            );
        }

        return (
            <>
                <div>
                    <dt className="font-semibold">Gold Spent</dt>
                    <dd>{formatNumber(batch.gold_spent_total ?? 0)}</dd>
                </div>
                <div>
                    <dt className="font-semibold">Gold Gained</dt>
                    <dd>{formatNumber(batch.gold_gained_total ?? 0)}</dd>
                </div>
                <div>
                    <dt className="font-semibold">Gold Left</dt>
                    <dd>{formatNumber(batch.gold_left ?? 0)}</dd>
                </div>
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
            <div>
                <dt className="font-semibold">Gold Dust Gained</dt>
                <dd>{formatNumber(gained)}</dd>
            </div>
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
            <dl className="grid gap-3 sm:grid-cols-2">
                {visibleRows.map((row) => (
                    <div key={row.label}>
                        <dt className="font-semibold">{row.label}</dt>
                        <dd>{row.value}</dd>
                    </div>
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
                <div className="space-y-3">
                    {visibleSkills.map((skill) => (
                        <div key={skill.key}>
                            <dl className="mb-1 flex items-center justify-between text-xs text-orange-700 dark:text-white">
                                <div>
                                    <dt className="sr-only">Skill</dt>
                                    <dd>
                                        {skill.name} (Lv {skill.level})
                                    </dd>
                                </div>
                                <div>
                                    <dt className="sr-only">Experience</dt>
                                    <dd>
                                        {skill.current_xp.toLocaleString()} /{" "}
                                        {skill.next_level_xp.toLocaleString()}
                                    </dd>
                                </div>
                            </dl>
                            <div className="h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                <div
                                    className="h-1.5 rounded-full bg-orange-600"
                                    style={{ width: `${skill.xp_percent}%` }}
                                    role="progressbar"
                                    aria-valuemin={0}
                                    aria-valuemax={100}
                                    aria-valuenow={skill.xp_percent}
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
        ].filter((row) => row.value > 0);

        if (rows.length === 0) {
            return null;
        }

        return (
            <dl className="grid gap-3 sm:grid-cols-2">
                {rows.map((row) => (
                    <div key={row.label}>
                        <dt className="font-semibold">{row.label}</dt>
                        <dd>{formatNumber(row.value)}</dd>
                    </div>
                ))}
            </dl>
        );
    }

    renderSnapshotList(
        pageKey: string,
        items: BatchCraftingSnapshotListItem[] | undefined,
        emptyMessage: string,
    ) {
        const snapshots = items ?? [];

        if (snapshots.length === 0) {
            return (
                <p className="text-sm text-gray-600 dark:text-gray-400">
                    {emptyMessage}
                </p>
            );
        }

        const totalPages = Math.max(1, Math.ceil(snapshots.length / perPage));
        const currentPage = this.itemPage(pageKey, totalPages);
        const start = (currentPage - 1) * perPage;
        const pageItems = snapshots
            .slice()
            .reverse()
            .slice(start, start + perPage);

        return (
            <div className="grid gap-3">
                <ul className="grid gap-2">
                    {pageItems.map((item, index) => (
                        <li
                            key={`${pageKey}-${item.display_name}-${item.crafted_at ?? index}`}
                            className="rounded-sm border border-gray-200 p-3 dark:border-gray-700"
                        >
                            <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                {this.renderItem(item.snapshot)}
                                <span className="text-xs text-gray-500 dark:text-gray-400">
                                    {formatLocalDateTime(item.crafted_at)}
                                </span>
                            </div>
                        </li>
                    ))}
                </ul>
                {totalPages > 1 ? (
                    <div className="flex items-center justify-between gap-3">
                        <button
                            type="button"
                            className="rounded border border-gray-300 px-3 py-1 text-sm disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600"
                            disabled={currentPage === 1}
                            onClick={() =>
                                this.setItemPage(pageKey, currentPage - 1)
                            }
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
                            onClick={() =>
                                this.setItemPage(pageKey, currentPage + 1)
                            }
                        >
                            Next
                        </button>
                    </div>
                ) : null}
            </div>
        );
    }

    renderCraftedItems(batch: NonNullable<BatchCraftingStatus["batch"]>) {
        return this.renderSnapshotList(
            "crafted-items",
            batch.crafted_item_snapshots,
            "No crafted items have been recorded yet.",
        );
    }

    renderActionHistory() {
        const actionLog = this.actionLog();

        if (actionLog.length === 0) {
            return null;
        }

        const currentPage = this.currentPage();
        const totalPages = this.totalPages();

        return (
            <div>
                <p className="mb-1 text-sm font-semibold">Action History</p>
                <p className="mb-2 text-xs text-gray-500 dark:text-gray-400">
                    Every action for this batch is recorded below. Counts above
                    are the source of truth for full batch progress.
                </p>
                <ul className="grid gap-3">
                    {this.entries().map((entry, index) => {
                        const item = primaryItem(entry);
                        const statusLabel = formatStatus(entry.status);

                        return (
                            <li
                                key={`${entry.ts}-${index}`}
                                className="border-b border-gray-200 pb-2 dark:border-gray-700"
                                aria-label={`${entry.action_type ?? "batch action"} ${item?.name ?? "item"} ${statusLabel}`}
                            >
                                <div className="flex flex-wrap gap-x-2 gap-y-1">
                                    <span className="text-gray-500 dark:text-gray-400">
                                        {formatLocalDateTime(entry.ts)}
                                    </span>
                                    <span className="font-semibold capitalize">
                                        {formatStatus(entry.action_type)}
                                    </span>
                                    <span className="capitalize text-gray-600 dark:text-gray-300">
                                        {statusLabel}
                                    </span>
                                </div>
                                <dl className="mt-1 grid gap-1 text-xs text-gray-600 dark:text-gray-400 sm:grid-cols-2">
                                    {entry.phase ? (
                                        <div>
                                            <dt className="font-semibold">
                                                Phase
                                            </dt>
                                            <dd className="capitalize">
                                                {formatStatus(entry.phase)}
                                            </dd>
                                        </div>
                                    ) : null}
                                    {entry.crafted_item &&
                                    entry.enchanted_item ? (
                                        <>
                                            <div>
                                                <dt className="font-semibold">
                                                    Attempted Item
                                                </dt>
                                                <dd>
                                                    {this.renderItem(
                                                        entry.crafted_item,
                                                    )}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt className="font-semibold">
                                                    Result Item
                                                </dt>
                                                <dd>
                                                    {this.renderItem(
                                                        entry.enchanted_item,
                                                    )}
                                                </dd>
                                            </div>
                                        </>
                                    ) : (
                                        <div>
                                            <dt className="font-semibold">
                                                Item
                                            </dt>
                                            <dd>{this.renderItem(item)}</dd>
                                        </div>
                                    )}
                                    {entry.prefix_affix_name ? (
                                        <div>
                                            <dt className="font-semibold">
                                                Prefix
                                            </dt>
                                            <dd>
                                                {entry.prefix_affix_name}
                                                {typeof entry.prefix_applied ===
                                                "boolean"
                                                    ? entry.prefix_applied
                                                        ? " (applied)"
                                                        : " (not applied)"
                                                    : ""}
                                            </dd>
                                        </div>
                                    ) : null}
                                    {entry.suffix_affix_name ? (
                                        <div>
                                            <dt className="font-semibold">
                                                Suffix
                                            </dt>
                                            <dd>
                                                {entry.suffix_affix_name}
                                                {typeof entry.suffix_applied ===
                                                "boolean"
                                                    ? entry.suffix_applied
                                                        ? " (applied)"
                                                        : " (not applied)"
                                                    : ""}
                                            </dd>
                                        </div>
                                    ) : null}
                                    {entry.oil_application?.oil_item ? (
                                        <div>
                                            <dt className="font-semibold">
                                                Oil
                                            </dt>
                                            <dd>
                                                {this.renderItem(
                                                    entry.oil_application
                                                        .oil_item,
                                                )}
                                            </dd>
                                        </div>
                                    ) : null}
                                    {entry.gold_spent ? (
                                        <div>
                                            <dt className="font-semibold">
                                                Gold Spent
                                            </dt>
                                            <dd>
                                                {entry.gold_spent.toLocaleString()}
                                            </dd>
                                        </div>
                                    ) : null}
                                    {entry.gold_gained ? (
                                        <div>
                                            <dt className="font-semibold">
                                                Gold Gained
                                            </dt>
                                            <dd>
                                                {entry.gold_gained.toLocaleString()}
                                            </dd>
                                        </div>
                                    ) : null}
                                    {entry.gold_dust_gained ? (
                                        <div>
                                            <dt className="font-semibold">
                                                Gold Dust Gained
                                            </dt>
                                            <dd>
                                                {entry.gold_dust_gained.toLocaleString()}
                                            </dd>
                                        </div>
                                    ) : null}
                                    {entry.destination_set ? (
                                        <div>
                                            <dt className="font-semibold">
                                                Destination Set
                                            </dt>
                                            <dd>{entry.destination_set}</dd>
                                        </div>
                                    ) : null}
                                    {entry.moved_to_set ? (
                                        <div>
                                            <dt className="font-semibold">
                                                Moved To Set
                                            </dt>
                                            <dd>Yes</dd>
                                        </div>
                                    ) : null}
                                    {entry.listed_price ? (
                                        <div>
                                            <dt className="font-semibold">
                                                Listed Price
                                            </dt>
                                            <dd>
                                                {entry.listed_price.toLocaleString()}
                                            </dd>
                                        </div>
                                    ) : null}
                                    {entry.failure ? (
                                        <div>
                                            <dt className="font-semibold text-red-700 dark:text-red-300">
                                                Failure Reason
                                            </dt>
                                            <dd className="text-red-700 dark:text-red-300">
                                                {entry.failure}
                                            </dd>
                                        </div>
                                    ) : null}
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
            </div>
        );
    }

    renderCharts(batch: NonNullable<BatchCraftingStatus["batch"]>) {
        const chartPoints = batch.chart_points;

        if (!chartPoints) {
            return null;
        }

        const currencyLines: ChartLine[] = [
            {
                label: "Currency Spent",
                data: chartPoints.currency.map((point) => ({
                    label: String(point.tick),
                    value: point.spent,
                })),
            },
            {
                label: "Currency Gained",
                data: chartPoints.currency.map((point) => ({
                    label: String(point.tick),
                    value: point.gained,
                })),
            },
        ];
        const outcomeLines: ChartLine[] = [
            {
                label: "Success",
                data: chartPoints.outcomes.map((point) => ({
                    label: String(point.tick),
                    value: point.success,
                })),
            },
            {
                label: "Failure",
                data: chartPoints.outcomes.map((point) => ({
                    label: String(point.tick),
                    value: point.failure,
                })),
            },
        ];

        return (
            <section className="grid gap-4 sm:grid-cols-2">
                <BatchLineChart
                    title="Currency Spent vs Gained"
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

    renderCraftAndEnchantExperienceCharts(
        batch: NonNullable<BatchCraftingStatus["batch"]>,
        showGoldDustChart: boolean,
    ) {
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
        const goldDustLines: ChartLine[] = [
            {
                label: "Gold Dust Gained",
                data: (chartPoints.gold_dust ?? []).map((point) => ({
                    label: String(point.tick),
                    value: point.gained,
                })),
            },
        ];

        return (
            <section className="grid gap-4 sm:grid-cols-2">
                <BatchLineChart
                    title="Success vs Failure"
                    lines={outcomeLines}
                    yAxisLabel="Count"
                />
                {showGoldDustChart ? (
                    <BatchLineChart
                        title="Gold Dust Gained Over Time"
                        lines={goldDustLines}
                        yAxisLabel="Gold Dust"
                    />
                ) : null}
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
                {onClose ? (
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

    renderAmountPreview(batch: NonNullable<BatchCraftingStatus["batch"]>) {
        const preview = batch.amount_preview;

        if (!preview) {
            return null;
        }

        return (
            <div className="grid gap-3">
                <InfoAlert additional_css="text-sm">
                    Kept output for this batch is moved into the Crafted Items
                    Set, not your normal inventory. You can sell or disenchant
                    items out of that set later.
                </InfoAlert>
                <dl className="grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt className="font-semibold">Selected Item</dt>
                        <dd>{this.renderItem(preview.selected_item)}</dd>
                    </div>
                    {preview.prefix_affix_name ? (
                        <div>
                            <dt className="font-semibold">Prefix</dt>
                            <dd>{preview.prefix_affix_name}</dd>
                        </div>
                    ) : null}
                    {preview.suffix_affix_name ? (
                        <div>
                            <dt className="font-semibold">Suffix</dt>
                            <dd>{preview.suffix_affix_name}</dd>
                        </div>
                    ) : null}
                    <div>
                        <dt className="font-semibold">Per Item Cost</dt>
                        <dd>{formatNumber(preview.total_per_item_cost)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Total Cost</dt>
                        <dd>{formatNumber(preview.total_cost)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Available Gold</dt>
                        <dd>{formatNumber(preview.available_gold)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">
                            Crafted Items Set Space
                        </dt>
                        <dd>
                            {formatNumber(preview.destination_current_slots)} /{" "}
                            {formatNumber(preview.destination_max_slots)} (
                            {formatNumber(preview.destination_remaining_slots)}{" "}
                            remaining)
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">
                            Effective Craftable Amount
                        </dt>
                        <dd>
                            {formatNumber(preview.effective_craftable_amount)}{" "}
                            of{" "}
                            {formatNumber(preview.remaining_requested_amount)}
                        </dd>
                    </div>
                    {preview.enchant_can_destroy_item ? (
                        <div>
                            <dt className="font-semibold">Enchanting Risk</dt>
                            <dd>
                                Enchanting can fail and destroy the item. Gold
                                is still spent even if that happens.
                            </dd>
                        </div>
                    ) : null}
                </dl>
                {preview.capped ? (
                    <WarningAlert>
                        This batch can only complete{" "}
                        {formatNumber(preview.effective_craftable_amount)} of
                        the requested{" "}
                        {formatNumber(preview.remaining_requested_amount)} items
                        with your current gold and Crafted Items Set space.
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
                        {isActive
                            ? `Crafting ${formatNumber(requested)} of ${item?.name ?? batch.current_item_name ?? "the selected item"}`
                            : `Crafted ${formatNumber(completed)} of ${formatNumber(requested)}`}
                    </p>
                </div>

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
                    percent={batch.progress_percent ?? 0}
                    barClassName="bg-orange-600"
                />

                <dl className="grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt className="font-semibold">Status</dt>
                        <dd>{this.statusText(batch, isActive)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Selected Item</dt>
                        <dd>{this.renderItem(item)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Current Item</dt>
                        <dd>{this.renderItem(item)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Requested</dt>
                        <dd>{formatNumber(requested)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Completed</dt>
                        <dd>{formatNumber(completed)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Remaining</dt>
                        <dd>{formatNumber(remaining)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Gold Spent</dt>
                        <dd>
                            {formatNumber(
                                batch.gold_spent_total ?? batch.gold_spent ?? 0,
                            )}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Gold Left</dt>
                        <dd>{formatNumber(batch.gold_left ?? 0)}</dd>
                    </div>
                </dl>

                {batch.batch_crafting_set ? (
                    <ProgressBar
                        label="Crafted Items Set Used"
                        current={batch.batch_crafting_set.current_slots}
                        max={batch.batch_crafting_set.max_slots}
                        percent={batch.batch_crafting_set.percent ?? 0}
                        barClassName="bg-regent-st-blue-500"
                    />
                ) : null}

                {this.renderAmountPreview(batch)}

                {this.renderSkillsList(batch.skills)}
                {this.renderUsefulCounts(batch)}
                {this.renderActionHistory()}

                <section>
                    <h4 className="mb-2 font-semibold">Completed Items</h4>
                    {this.renderCraftedItems(batch)}
                </section>

                {this.renderActionButtons(isActive, isSaving)}

                {this.renderOpenModals()}
            </div>
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
            "keep_best_disenchant_rest",
        ].includes(batch.disposition);

        return (
            <div className="space-y-4 text-sm" role="status" aria-live="polite">
                <div>
                    <h3 className="text-lg font-semibold">
                        Crafting for Experience
                    </h3>
                    <p className="mt-1 text-gray-700 dark:text-gray-300">
                        {item?.name
                            ? `Currently crafting ${item.name}`
                            : "Preparing to craft the next eligible item."}
                    </p>
                </div>

                <dl className="grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt className="font-semibold">Status</dt>
                        <dd className="capitalize">
                            {isActive
                                ? "running"
                                : formatStatus(batch.ended_reason)}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Current Item</dt>
                        <dd>{this.renderItem(item)}</dd>
                    </div>
                    {batch.last_action ? (
                        <div>
                            <dt className="font-semibold">Last Action</dt>
                            <dd>{batch.last_action}</dd>
                        </div>
                    ) : null}
                    {batch.next_action ? (
                        <div>
                            <dt className="font-semibold">Next Action</dt>
                            <dd>{batch.next_action}</dd>
                        </div>
                    ) : null}
                    <div>
                        <dt className="font-semibold">Items Crafted</dt>
                        <dd>{formatNumber(batch.counts.crafted)}</dd>
                    </div>
                    {this.renderCurrencyDetails(batch)}
                    {set && movesToCraftedItemsSet ? (
                        <div>
                            <dt className="font-semibold">Crafted Items Set</dt>
                            <dd>
                                {formatNumber(set.current_slots)} /{" "}
                                {formatNumber(set.max_slots)}
                            </dd>
                        </div>
                    ) : null}
                </dl>

                {this.renderSkillsList(batch.skills)}

                <section>
                    <h4 className="mb-2 font-semibold">Recent Crafted Items</h4>
                    {this.renderCraftedItems(batch)}
                </section>

                {this.renderUsefulCounts(batch)}
                {this.renderCharts(batch)}
                {this.renderActionHistory()}
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
        const set = batch.selected_set;
        const requested = batch.requested_amount ?? 0;
        const completed = batch.completed_amount ?? 0;
        const remaining =
            batch.remaining_amount ?? Math.max(0, requested - completed);
        const item =
            batch.craft_set_current_item ?? batch.current_item_snapshot;

        return (
            <div className="space-y-4 text-sm" role="status" aria-live="polite">
                <div>
                    <h3 className="text-lg font-semibold">Craft Set</h3>
                    <p className="mt-1 text-gray-700 dark:text-gray-300">
                        {isActive
                            ? `Crafting set entries into ${set?.name ?? "the selected set"}.`
                            : `Craft Set ended: ${formatStatus(batch.ended_reason)}.`}
                    </p>
                </div>

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
                    percent={batch.progress_percent ?? 0}
                    barClassName="bg-orange-600"
                />

                <dl className="grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt className="font-semibold">Status</dt>
                        <dd>{this.statusText(batch, isActive)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Selected Set</dt>
                        <dd>
                            {set
                                ? typeof set.max_slots === "number"
                                    ? `${set.name} (${set.current_slots} / ${set.max_slots})`
                                    : `${set.name} (${set.current_slots} used / unlimited)`
                                : "None"}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Remaining Slots</dt>
                        <dd>{set ? formatNumber(set.remaining_slots) : "—"}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Requested Entries</dt>
                        <dd>{formatNumber(requested)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Completed Entries</dt>
                        <dd>{formatNumber(completed)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Remaining Entries</dt>
                        <dd>{formatNumber(remaining)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Current Item</dt>
                        <dd>{this.renderItem(item)}</dd>
                    </div>
                    {this.renderCurrencyDetails(batch)}
                    <div>
                        <dt className="font-semibold">Skipped</dt>
                        <dd>{formatNumber(batch.counts.skipped)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Failed</dt>
                        <dd>{formatNumber(batch.counts.failed)}</dd>
                    </div>
                </dl>

                {this.renderSkillsList(batch.skills)}

                <section>
                    <h4 className="mb-2 font-semibold">Crafted Items</h4>
                    {this.renderCraftedItems(batch)}
                </section>

                {this.renderCharts(batch)}
                {this.renderActionHistory()}
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
        const set = batch.selected_set;
        const requested = batch.craft_enchant_set_requested ?? 0;
        const completedFinal =
            batch.craft_enchant_set_completed_final_count ?? 0;
        const remainingWorkUnits =
            batch.remaining_amount ?? Math.max(0, requested * 3);
        const item =
            batch.craft_enchant_set_current_item ?? batch.current_item_snapshot;
        const phaseLabels: Record<string, string> = {
            crafting: "Crafting set",
            enchanting: "Enchanting set",
            finalizing: "Finalizing set",
        };
        const phaseLabel = isActive
            ? (phaseLabels[batch.craft_enchant_set_phase ?? ""] ??
              "Crafting set")
            : formatStatus(batch.ended_reason);

        return (
            <div className="space-y-4 text-sm" role="status" aria-live="polite">
                <div>
                    <h3 className="text-lg font-semibold">
                        Craft and Enchant Set
                    </h3>
                    <p className="mt-1 text-gray-700 dark:text-gray-300">
                        {isActive
                            ? `Building and enchanting the full set into ${set?.name ?? "the selected set"}.`
                            : `Craft and Enchant Set ended: ${formatStatus(batch.ended_reason)}.`}
                    </p>
                </div>

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
                    label="Work Completed"
                    current={batch.completed_amount ?? 0}
                    max={requested * 3}
                    percent={batch.progress_percent ?? 0}
                    barClassName="bg-orange-600"
                />

                <dl className="grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt className="font-semibold">Status</dt>
                        <dd>{this.statusText(batch, isActive)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Phase</dt>
                        <dd className="capitalize">{phaseLabel}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Destination Set</dt>
                        <dd>
                            {set
                                ? typeof set.max_slots === "number"
                                    ? `${set.name} (${set.current_slots} / ${set.max_slots})`
                                    : `${set.name} (${set.current_slots} used / unlimited)`
                                : "None"}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Full Set Requested</dt>
                        <dd>{formatNumber(requested)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Crafted</dt>
                        <dd>{formatNumber(batch.counts.crafted)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">
                            Prefix Enchants Applied
                        </dt>
                        <dd>
                            {formatNumber(
                                batch.craft_enchant_set_prefix_applied_count ??
                                    0,
                            )}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">
                            Suffix Enchants Applied
                        </dt>
                        <dd>
                            {formatNumber(
                                batch.craft_enchant_set_suffix_applied_count ??
                                    0,
                            )}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Completed Final Items</dt>
                        <dd>{formatNumber(completedFinal)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Remaining Work Units</dt>
                        <dd>{formatNumber(remainingWorkUnits)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Current Item</dt>
                        <dd>{this.renderItem(item)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Current Prefix</dt>
                        <dd>
                            {batch.craft_enchant_set_current_prefix ?? "None"}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Current Suffix</dt>
                        <dd>
                            {batch.craft_enchant_set_current_suffix ?? "None"}
                        </dd>
                    </div>
                    {this.renderCurrencyDetails(batch)}
                    <div>
                        <dt className="font-semibold">Failed</dt>
                        <dd>{formatNumber(batch.counts.failed)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Skipped</dt>
                        <dd>{formatNumber(batch.counts.skipped)}</dd>
                    </div>
                </dl>

                <section>
                    <h4 className="mb-2 font-semibold">Output Items</h4>
                    {this.renderSnapshotList(
                        "craft-enchant-set-output-items",
                        batch.enchanted_item_snapshots,
                        "No completed set items have been recorded yet.",
                    )}
                </section>

                {this.renderCharts(batch)}
                {this.renderActionHistory()}
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

                <ProgressBar
                    label="Items Enchanted"
                    current={enchantedCount}
                    max={eligibleTotal}
                    percent={batch.progress_percent ?? 0}
                    barClassName="bg-orange-600"
                />

                <dl className="grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt className="font-semibold">Status</dt>
                        <dd>{this.statusText(batch, isActive)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Selected Set</dt>
                        <dd>
                            {set
                                ? `${set.name} (${set.current_slots} / ${set.max_slots})`
                                : "None"}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Eligible Items</dt>
                        <dd>{formatNumber(eligibleTotal)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Enchanted</dt>
                        <dd>{formatNumber(enchantedCount)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Remaining</dt>
                        <dd>{formatNumber(remainingCount)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Skipped</dt>
                        <dd>{formatNumber(skippedCount)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Failed</dt>
                        <dd>{formatNumber(batch.counts.failed)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Selected Enchantments</dt>
                        <dd>
                            {batch.enchant_affix_names &&
                            batch.enchant_affix_names.length > 0
                                ? batch.enchant_affix_names.join(", ")
                                : "None"}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Current Item</dt>
                        <dd>{this.renderItem(item)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Gold Spent</dt>
                        <dd>{formatNumber(batch.gold_spent_total ?? 0)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Gold Left</dt>
                        <dd>{formatNumber(batch.gold_left ?? 0)}</dd>
                    </div>
                </dl>

                <section>
                    <h4 className="mb-2 font-semibold">Enchanted Items</h4>
                    {this.renderSnapshotList(
                        "enchant-set-items",
                        batch.enchanted_item_snapshots,
                        "No enchanted items have been recorded yet.",
                    )}
                </section>

                {this.renderCharts(batch)}
                {this.renderActionHistory()}
                {this.renderActionButtons(isActive, isSaving)}
                {this.renderOpenModals()}
            </div>
        );
    }

    renderHolyOilItemsPreviewList(
        items: {
            item: BatchCraftingItemSnapshot | null;
            current_stacks: number;
            max_stacks: number;
            remaining_capacity: number;
            gold_dust_cost_per_application: number;
        }[],
    ) {
        if (items.length === 0) {
            return null;
        }

        return (
            <ul className="grid gap-2">
                {items.map((entry, index) => (
                    <li key={index}>
                        <dl className="grid grid-cols-2 gap-1 text-xs sm:grid-cols-4">
                            <div>
                                <dt className="font-semibold">Item</dt>
                                <dd>{this.renderItem(entry.item)}</dd>
                            </div>
                            <div>
                                <dt className="font-semibold">Stacks</dt>
                                <dd>
                                    {formatNumber(entry.current_stacks)} /{" "}
                                    {formatNumber(entry.max_stacks)}
                                </dd>
                            </div>
                            <div>
                                <dt className="font-semibold">Remaining</dt>
                                <dd>
                                    {formatNumber(entry.remaining_capacity)}
                                </dd>
                            </div>
                            <div>
                                <dt className="font-semibold">
                                    Gold Dust / Application
                                </dt>
                                <dd>
                                    {formatNumber(
                                        entry.gold_dust_cost_per_application,
                                    )}
                                </dd>
                            </div>
                        </dl>
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
                <dl className="grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt className="font-semibold">
                            Total Remaining Applications
                        </dt>
                        <dd>
                            {formatNumber(preview.total_remaining_applications)}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">
                            Selected Oils Available
                        </dt>
                        <dd>{formatNumber(preview.selected_oils_available)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Gold Dust Available</dt>
                        <dd>{formatNumber(preview.gold_dust_available)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">
                            Max Applications Possible
                        </dt>
                        <dd>
                            {formatNumber(preview.max_applications_possible)}
                        </dd>
                    </div>
                </dl>
                {preview.capped ? (
                    <WarningAlert>
                        This can apply{" "}
                        {formatNumber(preview.max_applications_possible)} of{" "}
                        {formatNumber(preview.total_remaining_applications)}{" "}
                        remaining Holy Oil stacks with your current oils and
                        gold dust.
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
                <dl className="grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt className="font-semibold">Total Eligible Items</dt>
                        <dd>{formatNumber(preview.total_eligible_items)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">
                            Total Remaining Applications
                        </dt>
                        <dd>
                            {formatNumber(preview.total_remaining_applications)}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">
                            Selected Oils Available
                        </dt>
                        <dd>{formatNumber(preview.selected_oils_available)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">
                            Max Applications Possible
                        </dt>
                        <dd>
                            {formatNumber(preview.max_applications_possible)}
                        </dd>
                    </div>
                </dl>
                {preview.capped && preview.capped_message ? (
                    <WarningAlert>{preview.capped_message}</WarningAlert>
                ) : null}
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

                <ProgressBar
                    label="Oils Applied"
                    current={completed}
                    max={requested}
                    percent={batch.progress_percent ?? 0}
                    barClassName="bg-orange-600"
                />

                <dl className="grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt className="font-semibold">Status</dt>
                        <dd>{this.statusText(batch, isActive)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Selected Set</dt>
                        <dd>
                            {set
                                ? `${set.name} (${set.current_slots} / ${set.max_slots})`
                                : "None"}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">
                            Total Eligible Set Items
                        </dt>
                        <dd>
                            {formatNumber(batch.holy_oil_eligible_items ?? 0)}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Total Holy Stacks</dt>
                        <dd>{formatNumber(totalStacks)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Oils Needed</dt>
                        <dd>{formatNumber(requested)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Oils Applied</dt>
                        <dd>{formatNumber(completed)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Oils Remaining</dt>
                        <dd>{formatNumber(remaining)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Skipped/Ineligible</dt>
                        <dd>
                            {formatNumber(batch.holy_oil_skipped_items ?? 0)}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">
                            Total Stat Bonus Applied
                        </dt>
                        <dd>
                            {(
                                batch.holy_oil_total_stat_bonus_applied ?? 0
                            ).toFixed(2)}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">
                            Total Devouring Darkness Bonus Applied
                        </dt>
                        <dd>
                            {(
                                batch.holy_oil_total_devouring_darkness_bonus_applied ??
                                0
                            ).toFixed(2)}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Current Target Item</dt>
                        <dd>
                            {this.renderItem(
                                batch.holy_oil_current_target_item,
                            )}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Current Oil Item</dt>
                        <dd>
                            {this.renderItem(batch.holy_oil_current_oil_item)}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Gold Dust Spent</dt>
                        <dd>
                            {formatNumber(batch.holy_oil_gold_dust_spent ?? 0)}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Gold Dust Left</dt>
                        <dd>{formatNumber(batch.gold_dust_left ?? 0)}</dd>
                    </div>
                </dl>

                {this.renderHolyOilsSetPreview(batch)}

                {this.renderCharts(batch)}
                {this.renderActionHistory()}
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
        const set = batch.batch_crafting_set;
        const movesToCraftedItemsSet = [
            "keep",
            "keep_highest",
            "keep_best_sell_rest",
            "keep_best_disenchant_rest",
        ].includes(batch.disposition);

        return (
            <div className="space-y-4 text-sm" role="status" aria-live="polite">
                <h3 className="text-lg font-semibold">
                    Craft and Enchant for Experience
                </h3>
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
                ])}
                {this.renderSkillsList(batch.skills)}
                <section>
                    <h4 className="mb-2 font-semibold">Crafted Items</h4>
                    {this.renderCraftedItems(batch)}
                </section>
                <section>
                    <h4 className="mb-2 font-semibold">Enchanted Items</h4>
                    {this.renderSnapshotList(
                        "craft-enchant-experience-enchanted-items",
                        batch.enchanted_item_snapshots,
                        "No enchanted items have been recorded yet.",
                    )}
                </section>
                {this.renderUsefulCounts(batch)}
                {this.renderCraftAndEnchantExperienceCharts(batch, disenchants)}
                {this.renderActionHistory()}
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
                <ProgressBar
                    label="Batch Progress"
                    current={completed}
                    max={requested}
                    percent={batch.progress_percent ?? 0}
                    barClassName="bg-orange-600"
                />
                {batch.disposition === "keep" && batch.batch_crafting_set ? (
                    <ProgressBar
                        label="Crafted Items Set Used"
                        current={batch.batch_crafting_set.current_slots}
                        max={batch.batch_crafting_set.max_slots}
                        percent={batch.batch_crafting_set.percent ?? 0}
                        barClassName="bg-regent-st-blue-500"
                    />
                ) : null}
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
                        value:
                            batch.enchant_affix_names &&
                            batch.enchant_affix_names.length > 0
                                ? batch.enchant_affix_names.join(", ")
                                : "None",
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
                        label: "Failed",
                        value: formatNumber(batch.counts.failed),
                    },
                    {
                        label: "Skipped",
                        value: formatNumber(batch.counts.skipped),
                    },
                ])}
                <section>
                    <h4 className="mb-2 font-semibold">Crafted Items</h4>
                    {this.renderCraftedItems(batch)}
                </section>
                <section>
                    <h4 className="mb-2 font-semibold">Enchanted Items</h4>
                    {this.renderSnapshotList(
                        "craft-enchant-specific-enchanted-items",
                        batch.enchanted_item_snapshots,
                        "No enchanted items have been recorded yet.",
                    )}
                </section>
                {this.renderActionHistory()}
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
                <section>
                    <h4 className="mb-2 font-semibold">Items</h4>
                    {this.renderSnapshotList(
                        isEnchant
                            ? "event-enchanted-items"
                            : "event-crafted-items",
                        isEnchant
                            ? batch.enchanted_item_snapshots
                            : batch.crafted_item_snapshots,
                        isEnchant
                            ? "No enchanted event items have been recorded yet."
                            : "No crafted event items have been recorded yet.",
                    )}
                </section>
                {this.renderCharts(batch)}
                {this.renderActionHistory()}
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
                ])}
                <section>
                    <h4 className="mb-2 font-semibold">Alchemy Items</h4>
                    {this.renderSnapshotList(
                        "alchemy-experience-items",
                        batch.alchemy_item_snapshots,
                        "No alchemy items have been recorded yet.",
                    )}
                </section>
                {this.renderCharts(batch)}
                {this.renderActionHistory()}
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
                <ProgressBar
                    label="Batch Progress"
                    current={completed}
                    max={requested}
                    percent={batch.progress_percent ?? 0}
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
                        label: "Gold Gained",
                        value: formatNumber(batch.gold_gained_total ?? 0),
                        show: (batch.gold_gained_total ?? 0) > 0,
                    },
                ])}
                {this.renderAlchemyAmountPreview(batch)}
                <section>
                    <h4 className="mb-2 font-semibold">Alchemy Items</h4>
                    {this.renderSnapshotList(
                        "alchemy-amount-items",
                        batch.alchemy_item_snapshots,
                        "No alchemy items have been recorded yet.",
                    )}
                </section>
                {this.renderActionHistory()}
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
                <dl className="grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt className="font-semibold">Selected Item</dt>
                        <dd>{this.renderItem(preview.selected_item)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Per Item Cost</dt>
                        <dd>
                            {formatNumber(preview.gold_dust_cost_per_item)} gold
                            dust
                            {preview.shards_cost_per_item > 0
                                ? `, ${formatNumber(preview.shards_cost_per_item)} shards`
                                : ""}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Total Cost</dt>
                        <dd>
                            {formatNumber(preview.total_gold_dust_cost)} gold
                            dust
                            {preview.total_shards_cost > 0
                                ? `, ${formatNumber(preview.total_shards_cost)} shards`
                                : ""}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Available Currency</dt>
                        <dd>
                            {formatNumber(preview.available_gold_dust)} gold
                            dust, {formatNumber(preview.available_shards)}{" "}
                            shards
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Alchemy Bag Space</dt>
                        <dd>
                            {formatNumber(preview.bag_current)} /{" "}
                            {formatNumber(preview.bag_max)} (
                            {formatNumber(preview.bag_remaining)} remaining)
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">
                            Effective Craftable Amount
                        </dt>
                        <dd>
                            {formatNumber(preview.effective_craftable_amount)}{" "}
                            of{" "}
                            {formatNumber(preview.remaining_requested_amount)}
                        </dd>
                    </div>
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
                        label: "Shards Left",
                        value: formatNumber(batch.currency?.amount ?? 0),
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
                <section>
                    <h4 className="mb-2 font-semibold">Trinkets</h4>
                    {this.renderSnapshotList(
                        "trinketry-items",
                        batch.trinketry_item_snapshots,
                        "No trinketry items have been recorded yet.",
                    )}
                </section>
                {this.renderCharts(batch)}
                {this.renderActionHistory()}
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
                <ProgressBar
                    label="Oils Applied"
                    current={completed}
                    max={requested}
                    percent={batch.progress_percent ?? 0}
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
                ])}
                {this.renderHolyOilsSelectedPreview(batch)}
                <section>
                    <h4 className="mb-2 font-semibold">Target Items</h4>
                    {this.renderSnapshotList(
                        "holy-oils-selected-target-items",
                        batch.holy_oil_target_item_snapshots,
                        "No holy oil target items have been recorded yet.",
                    )}
                </section>
                {this.renderActionHistory()}
                {this.renderActionButtons(isActive, isSaving)}
                {this.renderOpenModals()}
            </div>
        );
    }

    renderOpenModals() {
        const { character_id } = this.props;

        return (
            <React.Fragment>
                {this.state.openSlotId !== null ? (
                    <ItemDetailsModal
                        is_open={true}
                        character_id={character_id}
                        slot_id={this.state.openSlotId}
                        is_automation_running={true}
                        is_dead={false}
                        manage_modal={() => this.setOpenSlotId(null)}
                    />
                ) : null}
                {this.state.openSnapshot !== null ? (
                    <SnapshotDetailsModal
                        item={this.state.openSnapshot}
                        onClose={() => this.setOpenSnapshot(null)}
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
                <ProgressBar
                    label="Batch Progress"
                    current={batch.completed_amount ?? 0}
                    max={batch.requested_amount ?? 100}
                    percent={batch.progress_percent ?? 0}
                    barClassName="bg-orange-600"
                />
                {this.renderUsefulCounts(batch)}
                {this.renderActionHistory()}
                {this.renderActionButtons(isActive, isSaving)}
                {this.renderOpenModals()}
            </div>
        );
    }
}
