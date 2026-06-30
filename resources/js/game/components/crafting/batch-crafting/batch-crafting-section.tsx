import React, { useCallback, useEffect, useState } from "react";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import InfoAlert from "../../ui/alerts/simple-alerts/info-alert";
import DangerButton from "../../ui/buttons/danger-button";
import PrimaryButton from "../../ui/buttons/primary-button";
import Select from "react-select";
import { craftingGetEndPoints } from "../general-crafting/helpers/crafting-type-url";
import { BatchCraftingStatus } from "./batch-crafting-status-display";

type BatchType =
    | "craft"
    | "craft_and_enchant"
    | "alchemy"
    | "holy_oils"
    | "trinketry";

type CraftMode = "specific_item" | "experience";
type CraftCategory = "weapon" | "armour" | "ring" | "spell";
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
    gold_dust_cost?: number;
    shards_cost?: number;
};

type EnchantmentOption = {
    id: number;
    name: string;
    type: "prefix" | "suffix";
    cost?: number;
    int_required?: number;
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

const weaponTypeOptions = [
    { value: "dagger", label: "Daggers" },
    { value: "sword", label: "Swords" },
    { value: "claw", label: "Claws" },
    { value: "wand", label: "Wands" },
    { value: "censer", label: "Censers" },
    { value: "stave", label: "Staves" },
    { value: "hammer", label: "Hammers" },
    { value: "bow", label: "Bows" },
    { value: "gun", label: "Guns" },
    { value: "fan", label: "Fans" },
    { value: "mace", label: "Maces" },
    { value: "scratch-awl", label: "Scratch Awls" },
];

const armourTypeOptions = [
    { value: "helmet", label: "Helmet" },
    { value: "body", label: "Body" },
    { value: "sleeves", label: "Sleeves" },
    { value: "gloves", label: "Gloves" },
    { value: "shield", label: "Shields" },
    { value: "leggings", label: "Leggings" },
    { value: "feet", label: "Feet" },
];

const craftCategoryOptions: { value: CraftCategory; label: string }[] = [
    { value: "weapon", label: "Weapon" },
    { value: "armour", label: "Armour" },
    { value: "ring", label: "Ring" },
    { value: "spell", label: "Spell" },
];

function canList(batchType: BatchType): boolean {
    return ["craft_and_enchant", "alchemy"].includes(batchType);
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
    const [craftMode, setCraftMode] = useState<CraftMode>("experience");
    const [alchemyMode, setAlchemyMode] = useState<AlchemyMode>("experience");
    const [craftCategory, setCraftCategory] = useState<CraftCategory>("weapon");
    const [weaponType, setWeaponType] = useState("dagger");
    const [armourType, setArmourType] = useState("helmet");
    const [specificItemId, setSpecificItemId] = useState<number | null>(null);
    const [craftAmount, setCraftAmount] = useState<number | "">(1);
    const [craftableItems, setCraftableItems] = useState<CraftableItem[]>([]);
    const [alchemyItems, setAlchemyItems] = useState<CraftableItem[]>([]);
    const [selectedAlchemyItemId, setSelectedAlchemyItemId] = useState<
        number | null
    >(null);
    const [trinketryIsMaxed, setTrinketryIsMaxed] = useState(false);
    const [enchantments, setEnchantments] = useState<EnchantmentOption[]>([]);
    const [selectedPrefixId, setSelectedPrefixId] = useState<number | null>(
        null,
    );
    const [selectedSuffixId, setSelectedSuffixId] = useState<number | null>(
        null,
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

        const craftingType =
            craftCategory === "weapon"
                ? weaponType
                : craftCategory === "armour"
                  ? "armour"
                  : craftCategory;

        new Ajax()
            .setRoute(`crafting/${character_id}`)
            .setParameters({ crafting_type: craftingType })
            .doAjaxCall(
                "get",
                (response: AxiosResponse) => {
                    const items =
                        craftCategory === "armour"
                            ? (response.data.items ?? []).filter(
                                  (item: CraftableItem) =>
                                      item.type === armourType,
                              )
                            : (response.data.items ?? []);
                    setCraftableItems(items);
                    setSpecificItemId(items[0]?.id ?? null);
                },
                (_error: AxiosError) => {
                    setCraftableItems([]);
                    setSpecificItemId(null);
                },
            );
    }, [
        armourType,
        batchType,
        character_id,
        craftCategory,
        craftMode,
        weaponType,
    ]);

    useEffect(() => {
        if (batchType !== "alchemy" || alchemyMode !== "amount") {
            setAlchemyItems([]);
            setSelectedAlchemyItemId(null);
            return;
        }

        new Ajax()
            .setRoute(craftingGetEndPoints("alchemy", character_id))
            .doAjaxCall(
                "get",
                (response: AxiosResponse) => {
                    const items = response.data.items ?? [];
                    setAlchemyItems(items);
                    setSelectedAlchemyItemId(items[0]?.id ?? null);
                },
                (_error: AxiosError) => {
                    setAlchemyItems([]);
                    setSelectedAlchemyItemId(null);
                },
            );
    }, [alchemyMode, batchType, character_id]);

    useEffect(() => {
        if (batchType !== "trinketry") {
            setTrinketryIsMaxed(false);
            return;
        }

        new Ajax()
            .setRoute(craftingGetEndPoints("trinketry", character_id))
            .doAjaxCall(
                "get",
                (response: AxiosResponse) => {
                    const skillXp = response.data.skill_xp ?? {};
                    const nextLevelXp = Number(skillXp.next_level_xp ?? 0);
                    setTrinketryIsMaxed(
                        nextLevelXp > 0 &&
                            Number(skillXp.current_xp ?? 0) >= nextLevelXp,
                    );
                },
                (_error: AxiosError) => setTrinketryIsMaxed(false),
            );
    }, [batchType, character_id]);

    useEffect(() => {
        if (
            batchType !== "craft_and_enchant" ||
            craftMode !== "specific_item"
        ) {
            setEnchantments([]);
            setSelectedPrefixId(null);
            setSelectedSuffixId(null);
            return;
        }

        new Ajax().setRoute(`enchanting/${character_id}`).doAjaxCall(
            "get",
            (response: AxiosResponse) => {
                const affixes = response.data.affixes?.affixes ?? [];
                setEnchantments(affixes);
                setSelectedPrefixId(null);
                setSelectedSuffixId(null);
            },
            (_error: AxiosError) => {
                setEnchantments([]);
                setSelectedPrefixId(null);
                setSelectedSuffixId(null);
            },
        );
    }, [batchType, character_id, craftMode]);

    const startBatch = useCallback(() => {
        setIsSaving(true);
        setMessage("");

        const progress: Record<string, unknown> = {};

        if (batchType === "craft" || batchType === "craft_and_enchant") {
            progress.craft_mode = craftMode;

            if (craftMode === "specific_item") {
                progress.specific_crafting_type =
                    craftCategory === "weapon"
                        ? weaponType
                        : craftCategory === "armour"
                          ? "armour"
                          : craftCategory;
                progress.specific_item_id = specificItemId;
                progress.craft_amount =
                    craftAmount !== "" ? Number(craftAmount) : 1;

                if (batchType === "craft_and_enchant") {
                    progress.enchant_affix_ids = [
                        selectedPrefixId,
                        selectedSuffixId,
                    ].filter((affixId) => affixId !== null);
                }
            }
        }

        if (batchType === "alchemy") {
            progress.alchemy_mode = alchemyMode;

            if (alchemyMode === "amount") {
                progress.alchemy_amount =
                    craftAmount !== "" ? Number(craftAmount) : 1;
                progress.alchemy_item_id = selectedAlchemyItemId;
            }
        }

        if (batchType === "trinketry") {
            progress.trinketry_mode = "experience";
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
        craftCategory,
        weaponType,
        armourType,
        specificItemId,
        craftAmount,
        selectedPrefixId,
        selectedSuffixId,
        selectedAlchemyItemId,
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
    const startDisabled =
        isSaving || (batchType === "trinketry" && trinketryIsMaxed);

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
                                        (opt?.value ??
                                            "experience") as CraftMode,
                                    )
                                }
                                options={[
                                    {
                                        value: "experience",
                                        label: "Craft For Experience",
                                    },
                                    {
                                        value: "specific_item",
                                        label: "Specific Item",
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
                                        craftMode === "specific_item"
                                            ? "Specific Item"
                                            : "Craft For Experience",
                                }}
                            />
                        </label>
                    ) : null}

                    {(batchType === "craft" ||
                        batchType === "craft_and_enchant") &&
                    craftMode === "experience" ? (
                        <p className="rounded border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-100">
                            Experience mode uses internal 23-item sets and sends
                            kept output to the Crafted Items Set. It targets
                            Weapon Crafting, Armour Crafting, Ring Crafting, and
                            Spell Crafting. Craft and Enchant also enchants
                            crafted items before disposition rules. You may want
                            to equip relevant crafting or enchanting gear before
                            starting.
                        </p>
                    ) : null}

                    {(batchType === "craft" ||
                        batchType === "craft_and_enchant") &&
                    craftMode === "specific_item" ? (
                        <label className="grid gap-1 text-sm font-semibold">
                            Category
                            <Select
                                onChange={(opt) =>
                                    setCraftCategory(
                                        (opt?.value ??
                                            "weapon") as CraftCategory,
                                    )
                                }
                                options={craftCategoryOptions}
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
                                    value: craftCategory,
                                    label:
                                        craftCategoryOptions.find(
                                            (option) =>
                                                option.value === craftCategory,
                                        )?.label ?? "Weapon",
                                }}
                            />
                        </label>
                    ) : null}

                    {(batchType === "craft" ||
                        batchType === "craft_and_enchant") &&
                    craftMode === "specific_item" ? (
                        <>
                            {craftCategory === "weapon" ? (
                                <label className="grid gap-1 text-sm font-semibold">
                                    Weapon type
                                    <Select
                                        onChange={(opt) =>
                                            setWeaponType(
                                                opt?.value ?? "dagger",
                                            )
                                        }
                                        options={weaponTypeOptions}
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
                                            weaponTypeOptions.find(
                                                (option) =>
                                                    option.value === weaponType,
                                            ) ?? weaponTypeOptions[0]
                                        }
                                    />
                                </label>
                            ) : null}
                            {craftCategory === "armour" ? (
                                <label className="grid gap-1 text-sm font-semibold">
                                    Armour type
                                    <Select
                                        onChange={(opt) =>
                                            setArmourType(
                                                opt?.value ?? "helmet",
                                            )
                                        }
                                        options={armourTypeOptions}
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
                                            armourTypeOptions.find(
                                                (option) =>
                                                    option.value === armourType,
                                            ) ?? armourTypeOptions[0]
                                        }
                                    />
                                </label>
                            ) : null}
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
                                <>
                                    <label className="grid gap-1 text-sm font-semibold">
                                        Prefix enchant
                                        <Select
                                            isClearable
                                            onChange={(opt) =>
                                                setSelectedPrefixId(
                                                    opt?.value ?? null,
                                                )
                                            }
                                            options={enchantments
                                                .filter(
                                                    (enchantment) =>
                                                        enchantment.type ===
                                                        "prefix",
                                                )
                                                .map((enchantment) => ({
                                                    value: enchantment.id,
                                                    label: `${enchantment.name}${enchantment.cost ? ` Cost: ${enchantment.cost}` : ""}${enchantment.int_required ? `, INT REQ: ${enchantment.int_required}` : ""}`,
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
                                                enchantments
                                                    .filter(
                                                        (enchantment) =>
                                                            enchantment.type ===
                                                                "prefix" &&
                                                            enchantment.id ===
                                                                selectedPrefixId,
                                                    )
                                                    .map((enchantment) => ({
                                                        value: enchantment.id,
                                                        label: `${enchantment.name}${enchantment.cost ? ` Cost: ${enchantment.cost}` : ""}${enchantment.int_required ? `, INT REQ: ${enchantment.int_required}` : ""}`,
                                                    }))[0]
                                            }
                                        />
                                    </label>
                                    <label className="grid gap-1 text-sm font-semibold">
                                        Suffix enchant
                                        <Select
                                            isClearable
                                            onChange={(opt) =>
                                                setSelectedSuffixId(
                                                    opt?.value ?? null,
                                                )
                                            }
                                            options={enchantments
                                                .filter(
                                                    (enchantment) =>
                                                        enchantment.type ===
                                                        "suffix",
                                                )
                                                .map((enchantment) => ({
                                                    value: enchantment.id,
                                                    label: `${enchantment.name}${enchantment.cost ? ` Cost: ${enchantment.cost}` : ""}${enchantment.int_required ? `, INT REQ: ${enchantment.int_required}` : ""}`,
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
                                                enchantments
                                                    .filter(
                                                        (enchantment) =>
                                                            enchantment.type ===
                                                                "suffix" &&
                                                            enchantment.id ===
                                                                selectedSuffixId,
                                                    )
                                                    .map((enchantment) => ({
                                                        value: enchantment.id,
                                                        label: `${enchantment.name}${enchantment.cost ? ` Cost: ${enchantment.cost}` : ""}${enchantment.int_required ? `, INT REQ: ${enchantment.int_required}` : ""}`,
                                                    }))[0]
                                            }
                                        />
                                    </label>
                                </>
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
                                            "experience") as AlchemyMode,
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
                        <>
                            <label className="grid gap-1 text-sm font-semibold">
                                Alchemy item
                                <Select
                                    onChange={(opt) =>
                                        setSelectedAlchemyItemId(
                                            opt?.value ?? null,
                                        )
                                    }
                                    options={alchemyItems.map((item) => ({
                                        value: item.id,
                                        label: `${item.name}${item.gold_dust_cost ? ` Gold Dust Cost: ${item.gold_dust_cost}` : ""}${item.shards_cost ? ` Shards Cost: ${item.shards_cost}` : ""}`,
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
                                        selectedAlchemyItemId === null
                                            ? null
                                            : alchemyItems
                                                  .map((item) => ({
                                                      value: item.id,
                                                      label: `${item.name}${item.gold_dust_cost ? ` Gold Dust Cost: ${item.gold_dust_cost}` : ""}${item.shards_cost ? ` Shards Cost: ${item.shards_cost}` : ""}`,
                                                  }))
                                                  .find(
                                                      (item) =>
                                                          item.value ===
                                                          selectedAlchemyItemId,
                                                  )
                                    }
                                />
                            </label>
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

                    {batchType === "trinketry" ? (
                        trinketryIsMaxed ? (
                            <p className="rounded border border-red-300 bg-red-50 p-3 text-sm text-red-800 dark:border-red-700 dark:bg-red-950 dark:text-red-100">
                                doesnt make sense to batch craft trinket items
                                now does it? You are max level.
                            </p>
                        ) : null
                    ) : null}

                    {batchType === "holy_oils" ? (
                        <div className="grid gap-3">
                            <div>
                                {holyOilsLoading ? (
                                    <p
                                        className="text-sm text-gray-500"
                                        role="status"
                                        aria-live="polite"
                                    >
                                        Loading...
                                    </p>
                                ) : holyOilItems.length === 0 ? (
                                    <p className="text-sm text-gray-500 dark:text-gray-400">
                                        No items available for holy oils.
                                    </p>
                                ) : (
                                    <label className="grid gap-1 text-sm font-semibold">
                                        Items to apply holy oils to
                                        <Select
                                            isMulti
                                            onChange={(options) =>
                                                setSelectedItems(
                                                    options.map(
                                                        (option) =>
                                                            option.value,
                                                    ),
                                                )
                                            }
                                            options={holyOilItems.map(
                                                (slot) => ({
                                                    value: slot.item_id,
                                                    label: `${slot.item.name} (${slot.item.holy_stacks_applied}/${slot.item.holy_stacks} stacks)`,
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
                                            value={holyOilItems
                                                .filter((slot) =>
                                                    selectedItems.includes(
                                                        slot.item_id,
                                                    ),
                                                )
                                                .map((slot) => ({
                                                    value: slot.item_id,
                                                    label: `${slot.item.name} (${slot.item.holy_stacks_applied}/${slot.item.holy_stacks} stacks)`,
                                                }))}
                                        />
                                    </label>
                                )}
                            </div>

                            <div>
                                {holyOilsLoading ? (
                                    <p
                                        className="text-sm text-gray-500"
                                        role="status"
                                        aria-live="polite"
                                    >
                                        Loading...
                                    </p>
                                ) : holyOilOptions.length === 0 ? (
                                    <p className="text-sm text-gray-500 dark:text-gray-400">
                                        No holy oils available.
                                    </p>
                                ) : (
                                    <label className="grid gap-1 text-sm font-semibold">
                                        Holy oils to use
                                        <Select
                                            isMulti
                                            onChange={(options) =>
                                                setSelectedOils(
                                                    options.map(
                                                        (option) =>
                                                            option.value,
                                                    ),
                                                )
                                            }
                                            options={holyOilOptions.map(
                                                (slot) => ({
                                                    value: slot.id,
                                                    label: `${slot.item.name} (x${slot.amount})`,
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
                                            value={holyOilOptions
                                                .filter((slot) =>
                                                    selectedOils.includes(
                                                        slot.id,
                                                    ),
                                                )
                                                .map((slot) => ({
                                                    value: slot.id,
                                                    label: `${slot.item.name} (x${slot.amount})`,
                                                }))}
                                        />
                                    </label>
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
                            disabled={startDisabled}
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
