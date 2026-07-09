import React, { useCallback, useEffect, useState } from "react";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import AutomationPanelShell, {
    AutomationPanelTone,
} from "./automation-panel-shell";
import BatchCraftingStatusDisplay, {
    BatchCraftingStatus,
} from "../../../components/crafting/batch-crafting/batch-crafting-status-display";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import { updateTimers } from "../../../lib/ajax/update-timers";

type BatchCraftingStatusPanelProps = {
    character_id: number;
    user_id: number;
    onDismissed?: () => void;
};

const CLEAN_COMPLETION_END_REASONS = new Set([
    "completed_duration",
    "cancelled",
    "amount_reached",
    "craft_set_complete",
    "enchant_set_complete",
    "craft_enchant_set_complete",
    "all_oils_applied",
    "event_goal_complete",
]);

function toneForEndedReason(
    endedReason: string | null | undefined,
): AutomationPanelTone {
    if (!endedReason) {
        return "success";
    }

    return CLEAN_COMPLETION_END_REASONS.has(endedReason)
        ? "success"
        : "warning";
}

export default function BatchCraftingStatusPanel({
    character_id,
    user_id,
    onDismissed,
}: BatchCraftingStatusPanelProps) {
    const [status, setStatus] = useState<BatchCraftingStatus | null>(null);
    const [loading, setLoading] = useState(true);
    const [isSaving, setIsSaving] = useState(false);
    const [elapsedSeconds, setElapsedSeconds] = useState(0);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);
    const [statusError, setStatusError] = useState<string | null>(null);

    const fetchStatus = useCallback(() => {
        new Ajax().setRoute(`batch-crafting/${character_id}/status`).doAjaxCall(
            "get",
            (response: AxiosResponse) => {
                setStatus(response.data);
                setElapsedSeconds(response.data.batch?.elapsed_seconds ?? 0);
                setStatusError(null);
                setLoading(false);
            },
            (error: AxiosError) => {
                const response = error.response as AxiosResponse | undefined;
                setStatusError(
                    response?.data?.message ??
                        "Batch crafting status could not be loaded.",
                );
                setLoading(false);
            },
        );
    }, [character_id]);

    useEffect(() => {
        fetchStatus();
    }, [fetchStatus]);

    useEffect(() => {
        if (!status?.active) {
            return;
        }

        const interval = window.setInterval(() => {
            setElapsedSeconds((current) => current + 1);
        }, 1000);

        return () => window.clearInterval(interval);
    }, [status?.active]);

    useEffect(() => {
        const channelName = "batch-crafting-status-updated-" + user_id;
        const channel = window.Echo?.private(channelName);
        channel?.listen(".batch-crafting.status.updated", () => {
            fetchStatus();
        });

        return () => {
            channel?.stopListening(".batch-crafting.status.updated");
        };
    }, [user_id, fetchStatus]);

    const cancelBatch = useCallback(() => {
        setIsSaving(true);
        setErrorMessage(null);
        new Ajax().setRoute(`batch-crafting/${character_id}/cancel`).doAjaxCall(
            "post",
            (_response: AxiosResponse) => {
                setIsSaving(false);
                updateTimers(character_id);
                fetchStatus();
            },
            (error: AxiosError) => {
                setIsSaving(false);
                const response = error.response as AxiosResponse | undefined;
                setErrorMessage(
                    response?.data?.message ??
                        "Batch crafting could not be cancelled.",
                );
            },
        );
    }, [character_id, fetchStatus]);

    const dismissPanel = useCallback(() => {
        setIsSaving(true);
        setErrorMessage(null);
        new Ajax()
            .setRoute(`batch-crafting/${character_id}/dismiss`)
            .doAjaxCall(
                "post",
                (_response: AxiosResponse) => {
                    setIsSaving(false);
                    setStatus(null);
                    updateTimers(character_id);
                    window.dispatchEvent(
                        new CustomEvent("batch-crafting-hidden"),
                    );
                    onDismissed?.();
                },
                (error: AxiosError) => {
                    setIsSaving(false);
                    const response = error.response as
                        | AxiosResponse
                        | undefined;
                    setErrorMessage(
                        response?.data?.message ??
                            "Batch crafting could not be dismissed.",
                    );
                },
            );
    }, [character_id, fetchStatus, onDismissed]);

    if (loading) {
        return null;
    }

    if (statusError) {
        return (
            <AutomationPanelShell
                title="Batch Crafting"
                statusText="Error"
                tone="warning"
            >
                <DangerAlert additional_css="mb-3">{statusError}</DangerAlert>
                <button
                    type="button"
                    className="rounded-sm bg-blue-600 px-4 py-2 font-semibold text-white drop-shadow-sm hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-200 dark:bg-blue-700 dark:hover:bg-blue-600 dark:focus-visible:ring-white"
                    onClick={fetchStatus}
                >
                    Retry
                </button>
            </AutomationPanelShell>
        );
    }

    if (!status || (!status.active && !status.completed) || !status.batch) {
        return null;
    }

    const title =
        status.batch.human_mode_label ??
        (status.active ? "Batch Crafting In Progress" : "Batch Crafting Ended");
    const statusText = status.active
        ? "Running"
        : (status.batch.ended_reason ?? "Completed").replace(/_/g, " ");
    const timerText = status.active
        ? `${formatSeconds(elapsedSeconds)} elapsed`
        : `${status.batch.elapsed_human ?? "0s"} total`;
    const tone: AutomationPanelTone = status.active
        ? "neutral"
        : toneForEndedReason(status.batch.ended_reason);

    return (
        <AutomationPanelShell
            title={title}
            timerText={timerText}
            statusText={statusText}
            tone={tone}
        >
            {errorMessage ? (
                <DangerAlert
                    additional_css="mb-3"
                    close_alert={() => setErrorMessage(null)}
                >
                    {errorMessage}
                </DangerAlert>
            ) : null}
            <BatchCraftingStatusDisplay
                status={status}
                character_id={character_id}
                isSaving={isSaving}
                onCancel={cancelBatch}
                onDismiss={dismissPanel}
            />
        </AutomationPanelShell>
    );
}

function formatSeconds(seconds: number): string {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const remaining = seconds % 60;

    if (hours > 0) {
        return `${hours}h ${minutes}m ${remaining}s`;
    }

    if (minutes > 0) {
        return `${minutes}m ${remaining}s`;
    }

    return `${remaining}s`;
}
