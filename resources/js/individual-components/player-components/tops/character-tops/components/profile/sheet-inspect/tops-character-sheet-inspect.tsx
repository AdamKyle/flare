import React, { useState } from "react";
import TopsCharacterInfoTabs from "./tops-character-info-tabs";
import TopsCharacterSummaryCard from "./tops-character-summary-card";
import TopsCharacterSkills from "../skills/tops-character-skills";
import TopsCharacterInventoryTabs from "./tops-character-inventory-tabs";
import TopsCharacterAdditionalStats from "./tops-character-additional-stats";
import DangerButton from "../../../../../../../game/components/ui/buttons/danger-button";
import ItemDetails from "../../../../../../../game/sections/character-sheet/components/modals/components/item-details";

export default function TopsCharacterSheetInspect({
    profile,
}: {
    profile: any;
}) {
    const [showAdditionalStats, setShowAdditionalStats] = useState(false);
    const [selectedItem, setSelectedItem] = useState<any | null>(null);

    React.useEffect(() => {
        const handler = (event: KeyboardEvent) => {
            if (event.key === "Escape") {
                setSelectedItem(null);
            }
        };

        document.addEventListener("keydown", handler);

        return () => document.removeEventListener("keydown", handler);
    }, []);

    return (
        <div className="space-y-4">
            {showAdditionalStats ? (
                <>
                    <div className="flex justify-start">
                        <DangerButton
                            button_label={"Close"}
                            on_click={() => setShowAdditionalStats(false)}
                        />
                    </div>
                    <TopsCharacterAdditionalStats profile={profile} />
                </>
            ) : (
                <>
                    <div className="grid gap-4 xl:grid-cols-2">
                        <TopsCharacterInfoTabs
                            profile={profile}
                            manage_addition_data={() =>
                                setShowAdditionalStats(true)
                            }
                        />
                        <TopsCharacterSummaryCard profile={profile} />
                    </div>
                    <div className="grid gap-4 xl:grid-cols-2">
                        <TopsCharacterSkills profile={profile} />
                        <TopsCharacterInventoryTabs
                            profile={profile}
                            on_item_select={setSelectedItem}
                        />
                    </div>
                </>
            )}
            {selectedItem !== null ? (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="tops-item-detail-title"
                >
                    <div className="w-full max-w-5xl rounded-sm bg-white p-4 shadow-lg dark:bg-gray-800 dark:text-gray-100">
                        <div className="mb-4 flex items-start justify-between gap-4">
                            <h2
                                id="tops-item-detail-title"
                                className="text-xl font-semibold"
                            >
                                {selectedItem.item_name ??
                                    selectedItem.name ??
                                    "Item Details"}
                            </h2>
                            <button
                                type="button"
                                className="rounded-sm border border-gray-300 px-3 py-1 text-sm font-semibold dark:border-gray-600"
                                onClick={() => setSelectedItem(null)}
                            >
                                Close
                            </button>
                        </div>
                        <ItemDetails
                            item={selectedItem}
                            character_id={profile.overview?.id ?? 0}
                        />
                    </div>
                </div>
            ) : null}
        </div>
    );
}
