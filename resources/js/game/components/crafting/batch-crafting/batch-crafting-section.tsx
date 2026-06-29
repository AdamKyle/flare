import React, { useCallback, useEffect, useState } from "react";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import InfoAlert from "../../ui/alerts/simple-alerts/info-alert";
import DangerButton from "../../ui/buttons/danger-button";
import PrimaryButton from "../../ui/buttons/primary-button";
import Select from "react-select";
import BatchCraftingStatusDisplay, {
    BatchCraftingStatus,
} from "./batch-crafting-status-display";

type BatchType =
    | "craft"
    | "craft_and_enchant"
    | "enchant"
    | "alchemy"
    | "holy_oils"
    | "trinketry";

type CraftMode = "full_set" | "specific_item" | "experience";
type AlchemyMode = "experience" | "amount";

type Disposition =
    | "keep"
    | "keep_highest"
    | "sell"
    | "destroy"
    | "list"
    | "disenchant"
    | "keep_best_sell_rest"
    | "keep_best_disenchant_rest";

type CraftableItem = {
    id: number;
    name: string;
    type: string;
    cost?: number;
};

type EnchantmentOption = {
    id: number;
    name: string;
    cost?: number;
};

type HolyOilItem = {
    id: number;
    item_id: number;
    item: {
        name: string;
        holy_stacks: number;
        holy_stacks_applied: number;
    };
};

type HolyOilOption = {
    id: number;
    item_id: number;
    amount: number;
    item: {
        name: string;
    };
};

type BatchCraftingSectionProps = {
    character_id: number;
    user_id: number;
    remove_crafting: () => void;
};

const batchTypes: { value: BatchType; label: string }[] = [
    { value: "craft", label: "Craft" },
    { value: "craft_and_enchant", label: "Craft and Enchant" },
    { value: "enchant", label: "Enchant" },
    { value: "alchemy", label: "Alchemy" },
    { value: "holy_oils", label: "Holy Oils" },
    { value: "trinketry", label: "Trinketry" },
];

const dispositions: { value: Disposition; label: string }[] = [
    { value: "keep", label: "Keep" },
    { value: "keep_highest", label: "Keep Highest Level Crafted At The End" },
    { value: "sell", label: "Sell" },
    { value: "destroy", label: "Destroy" },
    { value: "list", label: "List" },
    { value: "disenchant", label: "Disenchant" },
    { value: "keep_best_sell_rest", label: "Keep Best and Sell Rest" },
    {
        value: "keep_best_disenchant_rest",
        label: "Keep Best and Disenchant Rest",
    },
];

function canList(batchType: BatchType): boolean {
    return ["craft_and_enchant", "enchant", "alchemy"].includes(batchType);
}

function canDisenchant(batchType: BatchType): boolean {
    return batchType === "craft_and_enchant";
}

function canKeepBestRest(batchType: BatchType): boolean {
    return batchType === "craft_and_enchant";
}

export default function BatchCraftingSection({
    character_id,
    user_id,
    remove_crafting,
}: BatchCraftingSectionProps) {
    const [status, setStatus] = useState<BatchCraftingStatus | null>(null);
    const [batchType, setBatchType] = useState<BatchType>("craft");
    const [disposition, setDisposition] = useState<Disposition>("keep");
    const [message, setMessage] = useState("");
    const [isSaving, setIsSaving] = useState(false);
    const [holyOilItems, setHolyOilItems] = useState<HolyOilItem[]>([]);
    const [holyOilOptions, setHolyOilOptions] = useState<HolyOilOption[]>([]);
    const [selectedItems, setSelectedItems] = useState<number[]>([]);
    const [selectedOils, setSelectedOils] = useState<number[]>([]);
    const [holyOilsLoading, setHolyOilsLoading] = useState(false);
    const [craftMode, setCraftMode] = useState<CraftMode>("full_set");
    const [alchemyMode, setAlchemyMode] = useState<AlchemyMode>("experience");
    const [specificCraftingType, setSpecificCraftingType] = useState("weapon");
    const [specificItemId, setSpecificItemId] = useState<number | null>(null);
    const [craftAmount, setCraftAmount] = useState<number | "">(1);
    const [trinketryAmount, setTrinketryAmount] = useState<number | "">(1);
    const [setCount, setSetCount] = useState<number | "">(1);
    const [craftableItems, setCraftableItems] = useState<CraftableItem[]>([]);
    const [enchantments, setEnchantments] = useState<EnchantmentOption[]>([]);
    const [selectedEnchantments, setSelectedEnchantments] = useState<number[]>(
        [],
    );

    const fetchStatus = useCallback(() => {
        new Ajax().setRoute(`batch-crafting/${character_id}/status`).doAjaxCall(
            "get",
            (response: AxiosResponse) => setStatus(response.data),
            (_error: AxiosError) =>
                setMessage("Could not load batch crafting status."),
        );
    }, [character_id]);

    const fetchHolyOilsData = useCallback(() => {
        setHolyOilsLoading(true);
        new Ajax()
            .setRoute(`character/${character_id}/inventory/smiths-workbench`)
            .doAjaxCall(
                "get",
                (response: AxiosResponse) => {
                    setHolyOilItems(response.data.items ?? []);
                    setHolyOilOptions(response.data.alchemy_items ?? []);
                    setHolyOilsLoading(false);
                },
                (_error: AxiosError) => setHolyOilsLoading(false),
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

    useEffect(() => {
        if (disposition === "list" && !canList(batchType)) {
            setDisposition("keep");
        }

        if (disposition === "disenchant" && !canDisenchant(batchType)) {
            setDisposition("keep");
        }

        if (
            ["keep_best_sell_rest", "keep_best_disenchant_rest"].includes(
                disposition,
            ) &&
            !canKeepBestRest(batchType)
        ) {
            setDisposition("keep");
        }
    }, [batchType, disposition]);

    useEffect(() => {
        if (batchType === "holy_oils") {
            fetchHolyOilsData();
            return;
        }

        setHolyOilItems([]);
        setHolyOilOptions([]);
        setSelectedItems([]);
        setSelectedOils([]);
    }, [batchType, fetchHolyOilsData]);

    useEffect(() => {
        if (
            (batchType !== "craft" && batchType !== "craft_and_enchant") ||
            craftMode !== "specific_item"
        ) {
            setCraftableItems([]);
            setSpecificItemId(null);
            return;
        }

        new Ajax()
            .setRoute(`crafting/${character_id}`)
            .setParameters({ crafting_type: specificCraftingType })
            .doAjaxCall(
                "get",
                (response: AxiosResponse) => {
                    const items = response.data.items ?? [];
                    setCraftableItems(items);
                    setSpecificItemId(items[0]?.id ?? null);
                },
                (_error: AxiosError) => {
                    setCraftableItems([]);
                    setSpecificItemId(null);
                },
            );
    }, [batchType, character_id, craftMode, specificCraftingType]);

    useEffect(() => {
        if (
            batchType !== "craft_and_enchant" ||
            craftMode !== "specific_item"
        ) {
            setEnchantments([]);
            setSelectedEnchantments([]);
            return;
        }

        new Ajax().setRoute(`enchanting/${character_id}`).doAjaxCall(
            "get",
            (response: AxiosResponse) => {
                const affixes = response.data.affixes?.affixes ?? [];
                setEnchantments(affixes);
                setSelectedEnchantments(affixes[0]?.id ? [affixes[0].id] : []);
            },
            (_error: AxiosError) => {
                setEnchantments([]);
                setSelectedEnchantments([]);
            },
        );
    }, [batchType, character_id, craftMode]);

    const toggleItemSelection = useCallback((itemId: number) => {
        setSelectedItems((currentItems) =>
            currentItems.includes(itemId)
                ? currentItems.filter((id) => id !== itemId)
                : [...currentItems, itemId],
        );
    }, []);

    const toggleOilSelection = useCallback((slotId: number) => {
        setSelectedOils((currentOils) =>
            currentOils.includes(slotId)
                ? currentOils.filter((id) => id !== slotId)
                : [...currentOils, slotId],
        );
    }, []);

    const startBatch = useCallback(() => {
        setIsSaving(true);
        setMessage("");

        const progress: Record<string, unknown> = {};

        if (batchType === "craft" || batchType === "craft_and_enchant") {
            progress.craft_mode = craftMode;

            if (craftMode === "specific_item") {
                progress.specific_crafting_type = specificCraftingType;
                progress.specific_item_id = specificItemId;
                progress.craft_amount =
                    craftAmount !== "" ? Number(craftAmount) : 1;

                if (batchType === "craft_and_enchant") {
                    progress.enchant_affix_ids = selectedEnchantments;
                }
            } else if (craftMode === "experience") {
                progress.specific_crafting_type = specificCraftingType;
            } else {
                progress.set_count = setCount !== "" ? Number(setCount) : 1;
            }
        }

        if (batchType === "alchemy") {
            progress.alchemy_mode = alchemyMode;

            if (alchemyMode === "amount") {
                progress.alchemy_amount =
                    craftAmount !== "" ? Number(craftAmount) : 1;
            }
        }

        if (batchType === "trinketry") {
            progress.trinketry_mode = "amount";
            progress.trinketry_amount =
                trinketryAmount !== "" ? Number(trinketryAmount) : 1;
        }

        const params: Record<string, unknown> = {
            batch_type: batchType,
            disposition: disposition,
            progress: progress,
        };

        if (batchType === "holy_oils") {
            params.selected_items = selectedItems;
            params.selected_oils = selectedOils;
        }

        new Ajax()
            .setRoute(`batch-crafting/${character_id}/start`)
            .setParameters(params)
            .doAjaxCall(
                "post",
                (_response: AxiosResponse) => {
                    setIsSaving(false);
                    fetchStatus();
                },
                (error: AxiosError) => {
                    const errorData = error.response?.data as {
                        message?: string;
                        errors?: Record<string, string[]>;
                    };
                    const firstError =
                        errorData?.errors !== undefined
                            ? Object.values(errorData.errors)[0]?.[0]
                            : null;

                    setIsSaving(false);
                    setMessage(
                        firstError ??
                            errorData?.message ??
                            "Could not start batch crafting.",
                    );
                },
            );
    }, [
        batchType,
        character_id,
        disposition,
        fetchStatus,
        selectedItems,
        selectedOils,
        craftMode,
        alchemyMode,
        specificCraftingType,
        specificItemId,
        craftAmount,
        trinketryAmount,
        setCount,
        selectedEnchantments,
    ]);

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
        new Ajax()
            .setRoute(`batch-crafting/${character_id}/dismiss`)
            .doAjaxCall(
                "post",
                (_response: AxiosResponse) => fetchStatus(),
                (_error: AxiosError) => {},
            );
    }, [character_id, fetchStatus]);

    const acknowledgeInfo = useCallback(() => {
        new Ajax()
            .setRoute(`batch-crafting/${character_id}/info/acknowledge`)
            .doAjaxCall(
                "post",
                (_response: AxiosResponse) => fetchStatus(),
                (_error: AxiosError) => {},
            );
    }, [character_id, fetchStatus]);

    const availableDispositions = dispositions.filter(
        (option) =>
            (option.value !== "list" || canList(batchType)) &&
            (option.value !== "disenchant" || canDisenchant(batchType)) &&
            (!["keep_best_sell_rest", "keep_best_disenchant_rest"].includes(
                option.value,
            ) ||
                canKeepBestRest(batchType)),
    );
    const selectedBatchType = batchTypes.find(
        (option) => option.value === batchType,
    );
    const selectedDisposition = availableDispositions.find(
        (option) => option.value === disposition,
    );
    const isActive = status?.active ?? false;
    const isCompleted = status?.completed ?? false;
    const hasBatch = (isActive || isCompleted) && status?.batch !== undefined;

    return (
        <section className="mt-2">
            <h3 className="text-center text-lg font-semibold text-gray-900 dark:text-gray-100">
                Batch Crafting
            </h3>

            {status?.show_info ? (
                <InfoAlert additional_css={"my-4"}>
                    <p className="mb-2">
                        Batch crafting allows you to keep exploring, delving,
                        and manually fighting while crafting, enchanting,
                        working alchemy items, applying holy oils, and making
                        trinkets.
                    </p>
                    <div className="flex flex-col gap-2 md:flex-row md:justify-center">
                        <a
                            href="/information/batch-crafting"
                            target="_blank"
                            rel="noreferrer"
                            className="text-center"
                        >
                            Help <i className="fas fa-external-link-alt"></i>
                        </a>
                        <button
                            type="button"
                            className="font-semibold text-blue-700 dark:text-blue-400"
                            onClick={acknowledgeInfo}
                        >
                            Acknowledge
                        </button>
                    </div>
                </InfoAlert>
            ) : null}

            {hasBatch && status?.batch ? (
                <div className="my-4">
                    <BatchCraftingStatusDisplay
                        status={status}
                        character_id={character_id}
                        isSaving={isSaving}
                        onCancel={cancelBatch}
                        onDismiss={dismissPanel}
                        onClose={remove_crafting}
                    />
                </div>
            ) : null}

            {!isActive && !isCompleted ? (
                <div className="mt-4 grid gap-3">
                    <label className="grid gap-1 text-sm font-semibold">
                        Crafting type
                        <Select
                            onChange={(selectedOption) => {
                                setBatchType(selectedOption?.value ?? "craft");
                            }}
                            options={batchTypes}
                            menuPosition={"absolute"}
                            menuPlacement={"bottom"}
                            styles={{
                                menuPortal: (base) => ({
                                    ...base,
                                    zIndex: 9999,
                                    color: "#000000",
                                }),
                            }}
                            menuPortalTarget={document.body}
                            value={selectedBatchType}
                        />
                    </label>

                    {batchType !== "holy_oils" ? (
                        <label className="grid gap-1 text-sm font-semibold">
                            Disposition
                            <Select
                                onChange={(selectedOption) => {
                                    setDisposition(
                                        selectedOption?.value ?? "keep",
                                    );
                                }}
                                options={availableDispositions}
                                menuPosition={"absolute"}
                                menuPlacement={"bottom"}
                                styles={{
                                    menuPortal: (base) => ({
                                        ...base,
                                        zIndex: 9999,
                                        color: "#000000",
                                    }),
                                }}
                                menuPortalTarget={document.body}
                                value={selectedDisposition}
                            />
                        </label>
                    ) : null}

                    {batchType === "craft" ||
                    batchType === "craft_and_enchant" ? (
                        <label className="grid gap-1 text-sm font-semibold">
                            Craft mode
                            <Select
                                onChange={(opt) =>
                                    setCraftMode(
                                        (opt?.value ?? "full_set") as CraftMode,
                                    )
                                }
                                options={[
                                    {
                                        value: "full_set",
                                        label: "Full Set (weapon, armour, ring, spell)",
                                    },
                                    {
                                        value: "specific_item",
                                        label: "Specific Item",
                                    },
                                    {
                                        value: "experience",
                                        label: "Craft For Experience",
                                    },
                                ]}
                                menuPosition={"absolute"}
                                menuPlacement={"bottom"}
                                styles={{
                                    menuPortal: (base) => ({
                                        ...base,
                                        zIndex: 9999,
                                        color: "#000000",
                                    }),
                                }}
                                menuPortalTarget={document.body}
                                value={{
                                    value: craftMode,
                                    label:
                                        craftMode === "full_set"
                                            ? "Full Set (weapon, armour, ring, spell)"
                                            : craftMode === "specific_item"
                                              ? "Specific Item"
                                              : "Craft For Experience",
                                }}
                            />
                        </label>
                    ) : null}

                    {(batchType === "craft" ||
                        batchType === "craft_and_enchant") &&
                    craftMode === "full_set" ? (
                        <label className="grid gap-1 text-sm font-semibold">
                            Set count
                            <input
                                className="rounded border border-gray-300 bg-white p-2 text-base dark:border-gray-600 dark:bg-gray-800"
                                type="number"
                                min={1}
                                value={setCount}
                                onChange={(e) =>
                                    setSetCount(
                                        e.target.value === ""
                                            ? ""
                                            : parseInt(e.target.value, 10),
                                    )
                                }
                            />
                        </label>
                    ) : null}

                    {(batchType === "craft" ||
                        batchType === "craft_and_enchant") &&
                    (craftMode === "specific_item" ||
                        craftMode === "experience") ? (
                        <label className="grid gap-1 text-sm font-semibold">
                            Crafting type
                            <Select
                                onChange={(opt) =>
                                    setSpecificCraftingType(
                                        opt?.value ?? "weapon",
                                    )
                                }
                                options={[
                                    { value: "weapon", label: "Weapon" },
                                    { value: "armour", label: "Armour" },
                                    { value: "ring", label: "Ring" },
                                    { value: "spell", label: "Spell" },
                                ]}
                                menuPosition={"absolute"}
                                menuPlacement={"bottom"}
                                styles={{
                                    menuPortal: (base) => ({
                                        ...base,
                                        zIndex: 9999,
                                        color: "#000000",
                                    }),
                                }}
                                menuPortalTarget={document.body}
                                value={{
                                    value: specificCraftingType,
                                    label:
                                        specificCraftingType
                                            .charAt(0)
                                            .toUpperCase() +
                                        specificCraftingType.slice(1),
                                }}
                            />
                        </label>
                    ) : null}

                    {(batchType === "craft" ||
                        batchType === "craft_and_enchant") &&
                    craftMode === "specific_item" ? (
                        <>
                            <label className="grid gap-1 text-sm font-semibold">
                                Item
                                <Select
                                    onChange={(opt) =>
                                        setSpecificItemId(opt?.value ?? null)
                                    }
                                    options={craftableItems.map((item) => ({
                                        value: item.id,
                                        label: `${item.name}${item.cost ? ` Gold Cost: ${item.cost}` : ""}`,
                                    }))}
                                    menuPosition={"absolute"}
                                    menuPlacement={"bottom"}
                                    styles={{
                                        menuPortal: (base) => ({
                                            ...base,
                                            zIndex: 9999,
                                            color: "#000000",
                                        }),
                                    }}
                                    menuPortalTarget={document.body}
                                    value={
                                        specificItemId === null
                                            ? null
                                            : craftableItems
                                                  .map((item) => ({
                                                      value: item.id,
                                                      label: `${item.name}${item.cost ? ` Gold Cost: ${item.cost}` : ""}`,
                                                  }))
                                                  .find(
                                                      (item) =>
                                                          item.value ===
                                                          specificItemId,
                                                  )
                                    }
                                />
                            </label>
                            {batchType === "craft_and_enchant" ? (
                                <label className="grid gap-1 text-sm font-semibold">
                                    Enchantments
                                    <Select
                                        isMulti
                                        onChange={(opts) =>
                                            setSelectedEnchantments(
                                                opts
                                                    .map((opt) => opt.value)
                                                    .slice(0, 2),
                                            )
                                        }
                                        options={enchantments.map(
                                            (enchantment) => ({
                                                value: enchantment.id,
                                                label: `${enchantment.name}${enchantment.cost ? ` Gold Cost: ${enchantment.cost}` : ""}`,
                                            }),
                                        )}
                                        menuPosition={"absolute"}
                                        menuPlacement={"bottom"}
                                        styles={{
                                            menuPortal: (base) => ({
                                                ...base,
                                                zIndex: 9999,
                                                color: "#000000",
                                            }),
                                        }}
                                        menuPortalTarget={document.body}
                                        value={enchantments
                                            .filter((enchantment) =>
                                                selectedEnchantments.includes(
                                                    enchantment.id,
                                                ),
                                            )
                                            .map((enchantment) => ({
                                                value: enchantment.id,
                                                label: `${enchantment.name}${enchantment.cost ? ` Gold Cost: ${enchantment.cost}` : ""}`,
                                            }))}
                                    />
                                </label>
                            ) : null}
                            <label className="grid gap-1 text-sm font-semibold">
                                Amount to craft
                                <input
                                    className="rounded border border-gray-300 bg-white p-2 text-base dark:border-gray-600 dark:bg-gray-800"
                                    type="number"
                                    min={1}
                                    value={craftAmount}
                                    onChange={(e) =>
                                        setCraftAmount(
                                            e.target.value === ""
                                                ? ""
                                                : parseInt(e.target.value, 10),
                                        )
                                    }
                                />
                            </label>
                        </>
                    ) : null}

                    {batchType === "alchemy" ? (
                        <label className="grid gap-1 text-sm font-semibold">
                            Alchemy mode
                            <Select
                                onChange={(opt) =>
                                    setAlchemyMode(
                                        (opt?.value ??
                                            "standard") as AlchemyMode,
                                    )
                                }
                                options={[
                                    {
                                        value: "experience",
                                        label: "For Experience",
                                    },
                                    { value: "amount", label: "Craft Amount" },
                                ]}
                                menuPosition={"absolute"}
                                menuPlacement={"bottom"}
                                styles={{
                                    menuPortal: (base) => ({
                                        ...base,
                                        zIndex: 9999,
                                        color: "#000000",
                                    }),
                                }}
                                menuPortalTarget={document.body}
                                value={{
                                    value: alchemyMode,
                                    label:
                                        alchemyMode === "experience"
                                            ? "For Experience"
                                            : "Craft Amount",
                                }}
                            />
                        </label>
                    ) : null}

                    {batchType === "alchemy" && alchemyMode === "amount" ? (
                        <label className="grid gap-1 text-sm font-semibold">
                            Amount to craft
                            <input
                                className="rounded border border-gray-300 bg-white p-2 text-base dark:border-gray-600 dark:bg-gray-800"
                                type="number"
                                min={1}
                                value={craftAmount}
                                onChange={(e) =>
                                    setCraftAmount(
                                        e.target.value === ""
                                            ? ""
                                            : parseInt(e.target.value, 10),
                                    )
                                }
                            />
                        </label>
                    ) : null}

                    {batchType === "trinketry" ? (
                        <label className="grid gap-1 text-sm font-semibold">
                            Amount to craft
                            <input
                                className="rounded border border-gray-300 bg-white p-2 text-base dark:border-gray-600 dark:bg-gray-800"
                                type="number"
                                min={1}
                                value={trinketryAmount}
                                onChange={(e) =>
                                    setTrinketryAmount(
                                        e.target.value === ""
                                            ? ""
                                            : parseInt(e.target.value, 10),
                                    )
                                }
                            />
                        </label>
                    ) : null}

                    {batchType === "holy_oils" ? (
                        <div className="grid gap-3">
                            <div>
                                <h4 className="mb-2 text-sm font-semibold">
                                    Items to apply holy oils to
                                </h4>
                                {holyOilsLoading ? (
                                    <p className="text-sm text-gray-500">
                                        Loading...
                                    </p>
                                ) : holyOilItems.length === 0 ? (
                                    <p className="text-sm text-gray-500 dark:text-gray-400">
                                        No items available for holy oils.
                                    </p>
                                ) : (
                                    <ul className="max-h-36 space-y-1 overflow-y-auto">
                                        {holyOilItems.map((slot) => (
                                            <li key={slot.id}>
                                                <label className="flex cursor-pointer items-center gap-2 text-sm">
                                                    <input
                                                        type="checkbox"
                                                        checked={selectedItems.includes(
                                                            slot.item_id,
                                                        )}
                                                        onChange={() =>
                                                            toggleItemSelection(
                                                                slot.item_id,
                                                            )
                                                        }
                                                    />
                                                    <span>
                                                        {slot.item.name}{" "}
                                                        <span className="text-xs text-gray-500">
                                                            (
                                                            {
                                                                slot.item
                                                                    .holy_stacks_applied
                                                            }
                                                            /
                                                            {
                                                                slot.item
                                                                    .holy_stacks
                                                            }{" "}
                                                            stacks)
                                                        </span>
                                                    </span>
                                                </label>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>

                            <div>
                                <h4 className="mb-2 text-sm font-semibold">
                                    Holy oils to use
                                </h4>
                                {holyOilsLoading ? (
                                    <p className="text-sm text-gray-500">
                                        Loading...
                                    </p>
                                ) : holyOilOptions.length === 0 ? (
                                    <p className="text-sm text-gray-500 dark:text-gray-400">
                                        No holy oils available.
                                    </p>
                                ) : (
                                    <ul className="max-h-36 space-y-1 overflow-y-auto">
                                        {holyOilOptions.map((slot) => (
                                            <li key={slot.id}>
                                                <label className="flex cursor-pointer items-center gap-2 text-sm">
                                                    <input
                                                        type="checkbox"
                                                        checked={selectedOils.includes(
                                                            slot.id,
                                                        )}
                                                        onChange={() =>
                                                            toggleOilSelection(
                                                                slot.id,
                                                            )
                                                        }
                                                    />
                                                    <span>
                                                        {slot.item.name}{" "}
                                                        <span className="text-xs text-gray-500">
                                                            (x{slot.amount})
                                                        </span>
                                                    </span>
                                                </label>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        </div>
                    ) : null}

                    {message !== "" ? (
                        <p className="text-sm text-red-700 dark:text-red-300">
                            {message}
                        </p>
                    ) : null}

                    <div className="flex flex-col items-center justify-center gap-2 md:flex-row">
                        <PrimaryButton
                            button_label={"Start Batch"}
                            on_click={startBatch}
                            disabled={isSaving}
                            additional_css={"w-full md:w-auto"}
                        />
                        <DangerButton
                            button_label={"Close"}
                            on_click={remove_crafting}
                            additional_css={"w-full md:w-auto"}
                            disabled={isSaving}
                        />
                        <a
                            href="/information/batch-crafting"
                            target="_blank"
                            rel="noreferrer"
                            className="block text-center md:ml-2"
                        >
                            Help <i className="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                </div>
            ) : null}
        </section>
    );
}
