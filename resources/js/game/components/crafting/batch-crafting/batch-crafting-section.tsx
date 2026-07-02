import React from "react";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import InfoAlert from "../../ui/alerts/simple-alerts/info-alert";
import DangerButton from "../../ui/buttons/danger-button";
import PrimaryButton from "../../ui/buttons/primary-button";
import Select from "react-select";
import { craftingGetEndPoints } from "../general-crafting/helpers/crafting-type-url";
import { BatchCraftingStatus } from "./batch-crafting-status-display";
import BatchCraftingSectionProps from "./types/batch-crafting-section-props";
import BatchCraftingSectionState from "./types/batch-crafting-section-state";
import {
    BatchType,
    CraftableItem,
    CraftCategory,
    Disposition,
} from "./types/batch-crafting-types";

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

export default class BatchCraftingSection extends React.Component<
    BatchCraftingSectionProps,
    BatchCraftingSectionState
> {
    public constructor(props: BatchCraftingSectionProps) {
        super(props);

        this.state = {
            status: null,
            batchType: "craft",
            disposition: "keep",
            message: "",
            isSaving: false,
            holyOilItems: [],
            holyOilOptions: [],
            selectedItems: [],
            selectedOils: [],
            holyOilsLoading: false,
            craftMode: "experience",
            alchemyMode: "experience",
            craftCategory: "weapon",
            weaponType: "dagger",
            armourType: "helmet",
            specificItemId: null,
            craftAmount: 1,
            craftableItems: [],
            alchemyItems: [],
            selectedAlchemyItemId: null,
            trinketryIsMaxed: false,
            enchantments: [],
            selectedPrefixId: null,
            selectedSuffixId: null,
        };
    }

    componentDidMount() {
        this.fetchStatus();
        this.listenForStatusUpdates();
        this.syncDependentState();
    }

    componentDidUpdate(
        _previousProps: BatchCraftingSectionProps,
        previousState: BatchCraftingSectionState,
    ) {
        if (
            previousState.batchType !== this.state.batchType ||
            previousState.disposition !== this.state.disposition ||
            previousState.craftMode !== this.state.craftMode ||
            previousState.alchemyMode !== this.state.alchemyMode ||
            previousState.craftCategory !== this.state.craftCategory ||
            previousState.weaponType !== this.state.weaponType ||
            previousState.armourType !== this.state.armourType
        ) {
            this.syncDependentState();
        }
    }

    componentWillUnmount() {
        window.Echo?.leave(
            "batch-crafting-status-updated-" + this.props.user_id,
        );
    }

    listenForStatusUpdates() {
        const channelName =
            "batch-crafting-status-updated-" + this.props.user_id;
        const channel = window.Echo?.private(channelName);

        channel?.listen(".batch-crafting.status.updated", () => {
            this.fetchStatus();
        });
    }

    fetchStatus() {
        new Ajax()
            .setRoute(`batch-crafting/${this.props.character_id}/status`)
            .doAjaxCall(
                "get",
                (response: AxiosResponse) =>
                    this.setState({
                        status: response.data,
                    }),
                (_error: AxiosError) =>
                    this.setState({
                        message: "Could not load batch crafting status.",
                    }),
            );
    }

    fetchHolyOilsData() {
        this.setState({
            holyOilsLoading: true,
        });
        new Ajax()
            .setRoute(
                `character/${this.props.character_id}/inventory/smiths-workbench`,
            )
            .doAjaxCall(
                "get",
                (response: AxiosResponse) => {
                    this.setState({
                        holyOilItems: response.data.items ?? [],
                        holyOilOptions: response.data.alchemy_items ?? [],
                        holyOilsLoading: false,
                    });
                },
                (_error: AxiosError) =>
                    this.setState({
                        holyOilsLoading: false,
                    }),
            );
    }

    syncDependentState() {
        const {
            alchemyMode,
            batchType,
            craftCategory,
            craftMode,
            disposition,
        } = this.state;

        if (disposition === "list" && !canList(batchType)) {
            this.setState({
                disposition: "keep",
            });
            return;
        }

        if (disposition === "disenchant" && !canDisenchant(batchType)) {
            this.setState({
                disposition: "keep",
            });
            return;
        }

        if (
            ["keep_best_sell_rest", "keep_best_disenchant_rest"].includes(
                disposition,
            ) &&
            !canKeepBestRest(batchType)
        ) {
            this.setState({
                disposition: "keep",
            });
            return;
        }

        if (batchType === "holy_oils") {
            this.fetchHolyOilsData();
            return;
        }

        if (
            this.state.holyOilItems.length > 0 ||
            this.state.holyOilOptions.length > 0 ||
            this.state.selectedItems.length > 0 ||
            this.state.selectedOils.length > 0
        ) {
            this.setState({
                holyOilItems: [],
                holyOilOptions: [],
                selectedItems: [],
                selectedOils: [],
            });
        }

        if (
            (batchType !== "craft" && batchType !== "craft_and_enchant") ||
            craftMode !== "specific_item"
        ) {
            if (
                this.state.craftableItems.length > 0 ||
                this.state.specificItemId !== null
            ) {
                this.setState({
                    craftableItems: [],
                    specificItemId: null,
                });
            }
        } else {
            this.fetchCraftableItems();
        }

        if (batchType !== "alchemy" || alchemyMode !== "amount") {
            if (
                this.state.alchemyItems.length > 0 ||
                this.state.selectedAlchemyItemId !== null
            ) {
                this.setState({
                    alchemyItems: [],
                    selectedAlchemyItemId: null,
                });
            }
        } else {
            this.fetchAlchemyItems();
        }

        if (batchType !== "trinketry") {
            if (this.state.trinketryIsMaxed) {
                this.setState({
                    trinketryIsMaxed: false,
                });
            }
        } else {
            this.fetchTrinketryState();
        }

        if (
            batchType !== "craft_and_enchant" ||
            craftMode !== "specific_item"
        ) {
            if (
                this.state.enchantments.length > 0 ||
                this.state.selectedPrefixId !== null ||
                this.state.selectedSuffixId !== null
            ) {
                this.setState({
                    enchantments: [],
                    selectedPrefixId: null,
                    selectedSuffixId: null,
                });
            }
        } else {
            this.fetchEnchantments();
        }
    }

    fetchCraftableItems() {
        const { armourType, craftCategory, weaponType } = this.state;
        const craftingType =
            craftCategory === "weapon"
                ? weaponType
                : craftCategory === "armour"
                  ? "armour"
                  : craftCategory;

        new Ajax()
            .setRoute(`crafting/${this.props.character_id}`)
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
                    this.setState({
                        craftableItems: items,
                        specificItemId: items[0]?.id ?? null,
                    });
                },
                (_error: AxiosError) => {
                    this.setState({
                        craftableItems: [],
                        specificItemId: null,
                    });
                },
            );
    }

    fetchAlchemyItems() {
        new Ajax()
            .setRoute(craftingGetEndPoints("alchemy", this.props.character_id))
            .doAjaxCall(
                "get",
                (response: AxiosResponse) => {
                    const items = response.data.items ?? [];
                    this.setState({
                        alchemyItems: items,
                        selectedAlchemyItemId: items[0]?.id ?? null,
                    });
                },
                (_error: AxiosError) => {
                    this.setState({
                        alchemyItems: [],
                        selectedAlchemyItemId: null,
                    });
                },
            );
    }

    fetchTrinketryState() {
        new Ajax()
            .setRoute(
                craftingGetEndPoints("trinketry", this.props.character_id),
            )
            .doAjaxCall(
                "get",
                (response: AxiosResponse) => {
                    const skillXp = response.data.skill_xp ?? {};
                    const nextLevelXp = Number(skillXp.next_level_xp ?? 0);
                    this.setState({
                        trinketryIsMaxed:
                            nextLevelXp > 0 &&
                            Number(skillXp.current_xp ?? 0) >= nextLevelXp,
                    });
                },
                (_error: AxiosError) =>
                    this.setState({
                        trinketryIsMaxed: false,
                    }),
            );
    }

    fetchEnchantments() {
        new Ajax().setRoute(`enchanting/${this.props.character_id}`).doAjaxCall(
            "get",
            (response: AxiosResponse) => {
                const affixes = response.data.affixes?.affixes ?? [];
                this.setState({
                    enchantments: affixes,
                    selectedPrefixId: null,
                    selectedSuffixId: null,
                });
            },
            (_error: AxiosError) => {
                this.setState({
                    enchantments: [],
                    selectedPrefixId: null,
                    selectedSuffixId: null,
                });
            },
        );
    }

    startBatch() {
        const {
            alchemyMode,
            batchType,
            craftAmount,
            craftCategory,
            craftMode,
            disposition,
            selectedAlchemyItemId,
            selectedItems,
            selectedOils,
            selectedPrefixId,
            selectedSuffixId,
            specificItemId,
            weaponType,
        } = this.state;

        this.setState({
            isSaving: true,
            message: "",
        });

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
            .setRoute(`batch-crafting/${this.props.character_id}/start`)
            .setParameters(params)
            .doAjaxCall(
                "post",
                (_response: AxiosResponse) => {
                    this.setState({
                        isSaving: false,
                    });
                    this.fetchStatus();
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

                    this.setState({
                        isSaving: false,
                        message:
                            firstError ??
                            errorData?.message ??
                            "Could not start batch crafting.",
                    });
                },
            );
    }

    cancelBatch() {
        this.setState({
            isSaving: true,
        });
        new Ajax()
            .setRoute(`batch-crafting/${this.props.character_id}/cancel`)
            .doAjaxCall(
                "post",
                (_response: AxiosResponse) => {
                    this.setState({
                        isSaving: false,
                    });
                    this.fetchStatus();
                },
                (_error: AxiosError) =>
                    this.setState({
                        isSaving: false,
                    }),
            );
    }

    dismissPanel() {
        new Ajax()
            .setRoute(`batch-crafting/${this.props.character_id}/dismiss`)
            .doAjaxCall(
                "post",
                (_response: AxiosResponse) => this.fetchStatus(),
                (_error: AxiosError) => {},
            );
    }

    acknowledgeInfo() {
        new Ajax()
            .setRoute(
                `batch-crafting/${this.props.character_id}/info/acknowledge`,
            )
            .doAjaxCall(
                "post",
                (_response: AxiosResponse) => this.fetchStatus(),
                (_error: AxiosError) => {},
            );
    }

    render() {
        const { character_id, remove_crafting } = this.props;
        const {
            alchemyItems,
            alchemyMode,
            armourType,
            batchType,
            craftAmount,
            craftCategory,
            craftMode,
            craftableItems,
            disposition,
            enchantments,
            holyOilItems,
            holyOilOptions,
            holyOilsLoading,
            isSaving,
            message,
            selectedAlchemyItemId,
            selectedItems,
            selectedOils,
            selectedPrefixId,
            selectedSuffixId,
            specificItemId,
            status,
            trinketryIsMaxed,
            weaponType,
        } = this.state;

        const setBatchType = (batchType: BatchType) =>
            this.setState({ batchType });
        const setDisposition = (disposition: Disposition) =>
            this.setState({ disposition });
        const setCraftMode = (craftMode: CraftMode) =>
            this.setState({ craftMode });
        const setAlchemyMode = (alchemyMode: AlchemyMode) =>
            this.setState({ alchemyMode });
        const setCraftCategory = (craftCategory: CraftCategory) =>
            this.setState({ craftCategory });
        const setWeaponType = (weaponType: string) =>
            this.setState({ weaponType });
        const setArmourType = (armourType: string) =>
            this.setState({ armourType });
        const setSpecificItemId = (specificItemId: number | null) =>
            this.setState({ specificItemId });
        const setCraftAmount = (craftAmount: number | "") =>
            this.setState({ craftAmount });
        const setSelectedAlchemyItemId = (
            selectedAlchemyItemId: number | null,
        ) => this.setState({ selectedAlchemyItemId });
        const setSelectedPrefixId = (selectedPrefixId: number | null) =>
            this.setState({ selectedPrefixId });
        const setSelectedSuffixId = (selectedSuffixId: number | null) =>
            this.setState({ selectedSuffixId });
        const setSelectedItems = (selectedItems: number[]) =>
            this.setState({ selectedItems });
        const setSelectedOils = (selectedOils: number[]) =>
            this.setState({ selectedOils });
        const startBatch = () => this.startBatch();
        const cancelBatch = () => this.cancelBatch();
        const dismissPanel = () => this.dismissPanel();
        const acknowledgeInfo = () => this.acknowledgeInfo();

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
                            Batch crafting allows you to keep exploring,
                            delving, and manually fighting while crafting,
                            enchanting, working alchemy items, applying holy
                            oils, and making trinkets.
                        </p>
                        <div className="flex flex-col gap-2 md:flex-row md:justify-center">
                            <a
                                href="/information/batch-crafting"
                                target="_blank"
                                rel="noreferrer"
                                className="text-center"
                            >
                                Help{" "}
                                <i className="fas fa-external-link-alt"></i>
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
                                    setBatchType(
                                        selectedOption?.value ?? "craft",
                                    );
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
                                Experience mode uses internal 23-item sets and
                                sends kept output to the Crafted Items Set. It
                                targets Weapon Crafting, Armour Crafting, Ring
                                Crafting, and Spell Crafting. Craft and Enchant
                                also enchants crafted items before disposition
                                rules. You may want to equip relevant crafting
                                or enchanting gear before starting.
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
                                                    option.value ===
                                                    craftCategory,
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
                                                        option.value ===
                                                        weaponType,
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
                                                        option.value ===
                                                        armourType,
                                                ) ?? armourTypeOptions[0]
                                            }
                                        />
                                    </label>
                                ) : null}
                                <label className="grid gap-1 text-sm font-semibold">
                                    Item
                                    <Select
                                        onChange={(opt) =>
                                            setSpecificItemId(
                                                opt?.value ?? null,
                                            )
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
                                                    : parseInt(
                                                          e.target.value,
                                                          10,
                                                      ),
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
                                        {
                                            value: "amount",
                                            label: "Craft Amount",
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
                                                    : parseInt(
                                                          e.target.value,
                                                          10,
                                                      ),
                                            )
                                        }
                                    />
                                </label>
                            </>
                        ) : null}

                        {batchType === "trinketry" ? (
                            trinketryIsMaxed ? (
                                <p className="rounded border border-red-300 bg-red-50 p-3 text-sm text-red-800 dark:border-red-700 dark:bg-red-950 dark:text-red-100">
                                    doesnt make sense to batch craft trinket
                                    items now does it? You are max level.
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
                                Help{" "}
                                <i className="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    </div>
                ) : null}
            </section>
        );
    }
}
