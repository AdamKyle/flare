import React, { useMemo, useState } from "react";
import DangerButton from "../../ui/buttons/danger-button";
import PrimaryButton from "../../ui/buttons/primary-button";
import ItemNameColorationText from "../../items/item-name/item-name-coloration-text";
import ItemDetailsModal from "../../modals/item-details/item-details-modal";

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
    listed_price?: number;
    failure?: string;
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

export type BatchCraftingStatus = {
    active: boolean;
    completed: boolean;
    show_info: boolean;
    batch?: {
        id: number;
        batch_label: string;
        batch_type: string;
        disposition: string;
        started_at: string | null;
        ends_at: string | null;
        completed_at: string | null;
        elapsed_seconds?: number;
        remaining_seconds?: number;
        elapsed_human?: string;
        remaining_human?: string;
        progress_percent?: number;
        requested_amount?: number | null;
        completed_amount?: number | null;
        ended_reason: string | null;
        status?: string;
        inventory_count: number;
        inventory_max: number;
        inventory_percent?: number;
        batch_crafting_set?: {
            current_slots: number;
            max_slots: number;
            remaining_slots: number;
        };
        skills?: BatchCraftingSkillData[];
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
            kept: number;
            applied: number;
            skipped: number;
            failed: number;
        };
        kept_set_summary?: {
            message: string;
            items: BatchCraftingItemSnapshot[];
        } | null;
        action_log?: BatchCraftingActionLogEntry[];
    };
};

type BatchCraftingStatusDisplayProps = {
    status: BatchCraftingStatus;
    character_id: number;
    isSaving: boolean;
    onCancel: () => void;
    onDismiss: () => void;
    onClose?: () => void;
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

export default function BatchCraftingStatusDisplay({
    status,
    character_id,
    isSaving,
    onCancel,
    onDismiss,
    onClose,
}: BatchCraftingStatusDisplayProps) {
    const [page, setPage] = useState(1);
    const [openSlotId, setOpenSlotId] = useState<number | null>(null);
    const batch = status.batch;
    const actionLog = batch?.action_log ?? [];
    const totalPages = Math.max(1, Math.ceil(actionLog.length / perPage));
    const currentPage = Math.min(page, totalPages);
    const entries = useMemo(() => {
        const start = (currentPage - 1) * perPage;
        return actionLog
            .slice()
            .reverse()
            .slice(start, start + perPage);
    }, [actionLog, currentPage]);

    if (!batch) {
        return null;
    }

    const isActive = status.active;

    const renderItem = (item: BatchCraftingItemSnapshot | null | undefined) => {
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
                        setOpenSlotId(item.slot_id_for_modal ?? null)
                    }
                >
                    {text}
                </button>
            );
        }

        return text;
    };

    return (
        <div
            className="space-y-4 text-sm"
            role="status"
            aria-live="polite"
            aria-label={
                isActive ? "Batch crafting running" : "Batch crafting ended"
            }
        >
            <dl className="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div className="rounded border border-gray-200 p-3 dark:border-gray-700">
                    <dt className="font-semibold">Type</dt>
                    <dd>{batch.batch_label}</dd>
                </div>
                <div className="rounded border border-gray-200 p-3 dark:border-gray-700">
                    <dt className="font-semibold">Status</dt>
                    <dd
                        className="capitalize"
                        role={
                            !isActive && batch.ended_reason
                                ? "alert"
                                : undefined
                        }
                        aria-atomic={
                            !isActive && batch.ended_reason ? "true" : undefined
                        }
                    >
                        {isActive
                            ? "Running"
                            : formatStatus(batch.ended_reason)}
                    </dd>
                </div>
                <div className="rounded border border-gray-200 p-3 dark:border-gray-700">
                    <dt className="font-semibold">Elapsed</dt>
                    <dd>{batch.elapsed_human ?? "0s"}</dd>
                </div>
                {isActive ? (
                    <div className="rounded border border-gray-200 p-3 dark:border-gray-700">
                        <dt className="font-semibold">Remaining</dt>
                        <dd>{batch.remaining_human ?? "0s"}</dd>
                    </div>
                ) : null}
                <div className="rounded border border-gray-200 p-3 dark:border-gray-700">
                    <dt className="font-semibold">Currency</dt>
                    <dd>
                        {batch.currency.amount.toLocaleString()}{" "}
                        {batch.currency.type}
                    </dd>
                </div>
                <div className="rounded border border-gray-200 p-3 dark:border-gray-700">
                    <dt className="font-semibold">Inventory</dt>
                    <dd>
                        {batch.inventory_count} / {batch.inventory_max}
                    </dd>
                </div>
                {batch.batch_crafting_set ? (
                    <div className="rounded border border-gray-200 p-3 dark:border-gray-700">
                        <dt className="font-semibold">Crafted Items Set</dt>
                        <dd>
                            {batch.batch_crafting_set.current_slots} /{" "}
                            {batch.batch_crafting_set.max_slots}
                        </dd>
                    </div>
                ) : null}
            </dl>

            <div className="rounded border border-gray-200 p-3 dark:border-gray-700">
                <div className="mb-1 flex justify-between text-xs text-orange-700 dark:text-white">
                    <span>Inventory Used</span>
                    <span>
                        {batch.inventory_count} / {batch.inventory_max}
                    </span>
                </div>
                <div className="h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                    <div
                        className="h-1.5 rounded-full bg-orange-600"
                        style={{ width: `${batch.inventory_percent ?? 0}%` }}
                        role="progressbar"
                        aria-valuemin={0}
                        aria-valuemax={100}
                        aria-valuenow={batch.inventory_percent ?? 0}
                    />
                </div>
            </div>

            {batch.skills && batch.skills.length > 0 ? (
                <div className="rounded border border-gray-200 p-3 dark:border-gray-700">
                    <h4 className="mb-2 text-sm font-semibold">Skills</h4>
                    <div className="space-y-2">
                        {batch.skills.map((skill) => (
                            <div key={skill.key}>
                                <div className="mb-1 flex justify-between text-xs text-orange-700 dark:text-white">
                                    <span>
                                        {skill.name} (Lv {skill.level})
                                        {skill.is_maxed ? " — Maxed" : ""}
                                    </span>
                                    {!skill.is_maxed ? (
                                        <span>
                                            {skill.current_xp.toLocaleString()}{" "}
                                            /{" "}
                                            {skill.next_level_xp.toLocaleString()}
                                        </span>
                                    ) : null}
                                </div>
                                <div className="h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                    <div
                                        className="h-1.5 rounded-full bg-orange-600"
                                        style={{
                                            width: `${skill.xp_percent}%`,
                                        }}
                                        role="progressbar"
                                        aria-valuemin={0}
                                        aria-valuemax={100}
                                        aria-valuenow={skill.xp_percent}
                                    />
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            ) : null}

            <div className="rounded border border-gray-200 p-3 dark:border-gray-700">
                {batch.requested_amount &&
                typeof batch.completed_amount === "number" ? (
                    <div className="mb-1 flex justify-between text-xs text-orange-700 dark:text-white">
                        <span>Completed</span>
                        <span>
                            {batch.completed_amount.toLocaleString()} /{" "}
                            {batch.requested_amount.toLocaleString()}
                        </span>
                    </div>
                ) : null}
                <div className="h-3 overflow-hidden rounded bg-gray-200 dark:bg-gray-700">
                    <div
                        className="h-full bg-orange-500"
                        style={{ width: `${batch.progress_percent ?? 0}%` }}
                        role="progressbar"
                        aria-valuemin={0}
                        aria-valuemax={100}
                        aria-valuenow={batch.progress_percent ?? 0}
                    />
                </div>
                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Progress: {batch.progress_percent ?? 0}%
                </p>
            </div>

            <div className="grid grid-cols-2 gap-2 rounded border border-gray-200 p-3 text-xs dark:border-gray-700 md:grid-cols-5">
                <span>Crafted: {batch.counts.crafted}</span>
                <span>Sold: {batch.counts.sold}</span>
                <span>Destroyed: {batch.counts.destroyed}</span>
                <span>Listed: {batch.counts.listed}</span>
                <span>Disenchanted: {batch.counts.disenchanted ?? 0}</span>
                <span>Kept: {batch.counts.kept}</span>
                <span>Applied: {batch.counts.applied}</span>
                <span>Skipped: {batch.counts.skipped}</span>
                <span>Failed: {batch.counts.failed}</span>
            </div>

            {batch.kept_set_summary ? (
                <div className="my-4 rounded border border-amber-300 bg-amber-50 p-3 text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
                    <p>{batch.kept_set_summary.message}</p>
                    <ul className="mt-2 grid gap-1">
                        {batch.kept_set_summary.items.map((item, index) => (
                            <li key={index}>{renderItem(item)}</li>
                        ))}
                    </ul>
                </div>
            ) : null}

            {actionLog.length > 0 ? (
                <div className="rounded border border-gray-200 p-3 dark:border-gray-700">
                    <h4 className="mb-2 text-sm font-semibold">
                        Action History
                    </h4>
                    <p className="mb-2 text-xs text-gray-500 dark:text-gray-400">
                        Showing latest 50 actions. Counts above are the source
                        of truth for full batch progress.
                    </p>
                    <ul className="grid gap-3">
                        {entries.map((entry, index) => {
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
                                            {new Date(
                                                entry.ts,
                                            ).toLocaleString()}
                                        </span>
                                        <span className="font-semibold capitalize">
                                            {formatStatus(entry.action_type)}
                                        </span>
                                        <span className="capitalize text-gray-600 dark:text-gray-300">
                                            {statusLabel}
                                        </span>
                                    </div>
                                    <div className="mt-1">
                                        {renderItem(item)}
                                        {entry.oil_application?.oil_item ? (
                                            <span className="ml-2">
                                                Oil:{" "}
                                                {renderItem(
                                                    entry.oil_application
                                                        .oil_item,
                                                )}
                                            </span>
                                        ) : null}
                                    </div>
                                    {entry.gold_gained ? (
                                        <p className="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                            Gold gained:{" "}
                                            {entry.gold_gained.toLocaleString()}
                                        </p>
                                    ) : null}
                                    {entry.gold_dust_gained ? (
                                        <p className="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                            Gold dust gained:{" "}
                                            {entry.gold_dust_gained.toLocaleString()}
                                        </p>
                                    ) : null}
                                    {entry.listed_price ? (
                                        <p className="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                            Listed for:{" "}
                                            {entry.listed_price.toLocaleString()}
                                        </p>
                                    ) : null}
                                    {entry.failure ? (
                                        <p className="mt-1 text-xs text-red-700 dark:text-red-300">
                                            {entry.failure}
                                        </p>
                                    ) : null}
                                </li>
                            );
                        })}
                    </ul>
                    <div className="mt-3 flex items-center justify-between gap-3">
                        <button
                            type="button"
                            className="rounded border border-gray-300 px-3 py-1 text-sm disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600"
                            disabled={currentPage === 1}
                            onClick={() => setPage(currentPage - 1)}
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
                            onClick={() => setPage(currentPage + 1)}
                        >
                            Next
                        </button>
                    </div>
                </div>
            ) : null}

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

            {openSlotId !== null ? (
                <ItemDetailsModal
                    is_open={true}
                    character_id={character_id}
                    slot_id={openSlotId}
                    is_automation_running={true}
                    is_dead={false}
                    manage_modal={() => setOpenSlotId(null)}
                />
            ) : null}
        </div>
    );
}
