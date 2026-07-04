import React, { useCallback, useEffect, useState } from "react";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import AutomationPanelShell from "./automation-panel-shell";
import BatchCraftingStatusDisplay, {
    BatchCraftingStatus,
} from "../../../components/crafting/batch-crafting/batch-crafting-status-display";

type BatchCraftingStatusPanelProps = {
    character_id: number;
    user_id: number;
};

export default function BatchCraftingStatusPanel({
    character_id,
    user_id,
}: BatchCraftingStatusPanelProps) {
    const [status, setStatus] = useState<BatchCraftingStatus | null>(null);
    const [loading, setLoading] = useState(true);
    const [isSaving, setIsSaving] = useState(false);
    const [elapsedSeconds, setElapsedSeconds] = useState(0);

    const fetchStatus = useCallback(() => {
        new Ajax().setRoute(`batch-crafting/${character_id}/status`).doAjaxCall(
            "get",
            (response: AxiosResponse) => {
                setStatus(response.data);
                setElapsedSeconds(response.data.batch?.elapsed_seconds ?? 0);
                setLoading(false);
            },
            (_error: AxiosError) => {
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
            window.Echo?.leave(channelName);
        };
    }, [user_id, fetchStatus]);

    const cancelBatch = useCallback(() => {
        setIsSaving(true);
        new Ajax().setRoute(`batch-crafting/${character_id}/cancel`).doAjaxCall(
            "post",
            (_response: AxiosResponse) => {
                setIsSaving(false);
                fetchStatus();
            },
            (_error: AxiosError) => setIsSaving(false),
        );
    }, [character_id, fetchStatus]);

    const dismissPanel = useCallback(() => {
        setIsSaving(true);
        new Ajax()
            .setRoute(`batch-crafting/${character_id}/dismiss`)
            .doAjaxCall(
                "post",
                (_response: AxiosResponse) => {
                    setIsSaving(false);
                    setStatus(null);
                    window.dispatchEvent(
                        new CustomEvent("batch-crafting-hidden"),
                    );
                },
                (_error: AxiosError) => setIsSaving(false),
            );
    }, [character_id, fetchStatus]);

    if (loading) {
        return null;
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

    return (
        <AutomationPanelShell
            title={title}
            timerText={timerText}
            statusText={statusText}
        >
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
