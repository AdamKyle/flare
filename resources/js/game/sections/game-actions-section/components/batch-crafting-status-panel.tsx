import React, { useCallback, useEffect, useState } from "react";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
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

    const fetchStatus = useCallback(() => {
        new Ajax().setRoute(`batch-crafting/${character_id}/status`).doAjaxCall(
            "get",
            (response: AxiosResponse) => {
                setStatus(response.data);
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
                    fetchStatus();
                },
                (_error: AxiosError) => setIsSaving(false),
            );
    }, [character_id, fetchStatus]);

    if (loading) {
        return (
            <div className="mt-3">
                <LoadingProgressBar />
            </div>
        );
    }

    if (!status || (!status.active && !status.completed) || !status.batch) {
        return null;
    }

    const title = status.active
        ? "Batch Crafting In Progress"
        : "Batch Crafting Ended";
    const statusText = status.active
        ? "Running"
        : (status.batch.ended_reason ?? "Completed").replace(/_/g, " ");
    const timerText = status.active
        ? `${status.batch.elapsed_human ?? "0s"} elapsed`
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
