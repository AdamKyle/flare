import React, { useEffect, useState } from "react";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError } from "axios";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import QuestItem from "../../../components/modals/item-details/item-views/quest-item";

interface DelveQuestItemModalProps {
    item_id: number;
    character_id: number;
    item_name: string;
    is_open: boolean;
    manage_modal: () => void;
}

export default function DelveQuestItemModal({
    item_id,
    character_id,
    item_name,
    is_open,
    manage_modal,
}: DelveQuestItemModalProps) {
    const [itemData, setItemData] = useState<any | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (!is_open) {
            return;
        }

        setLoading(true);
        setError(null);
        setItemData(null);

        new Ajax()
            .setRoute(`delve/${character_id}/quest-item/${item_id}`)
            .doAjaxCall(
                "get",
                (response: { data: { item: any } }) => {
                    setItemData(response.data.item);
                    setLoading(false);
                },
                (_error: AxiosError) => {
                    setError("Could not load item details.");
                    setLoading(false);
                },
            );
    }, [is_open, item_id, character_id]);

    return (
        <Dialogue
            is_open={is_open}
            handle_close={manage_modal}
            title={item_name}
            large_modal={true}
        >
            {loading ? (
                <LoadingProgressBar />
            ) : error !== null ? (
                <DangerAlert>{error}</DangerAlert>
            ) : itemData !== null ? (
                <QuestItem item={itemData} />
            ) : null}
        </Dialogue>
    );
}
