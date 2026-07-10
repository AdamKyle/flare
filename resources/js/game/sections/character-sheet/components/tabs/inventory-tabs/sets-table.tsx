import React, { Fragment } from "react";
import Table from "../../../../../components/ui/data-tables/table";
import { BuildInventoryTableColumns } from "../../../../../lib/game/character-sheet/helpers/inventory/build-inventory-table-columns";
import DropDown from "../../../../../components/ui/drop-down/drop-down";
import InventoryDetails from "../../../../../lib/game/character-sheet/types/inventory/inventory-details";
import ActionsInterface from "../../../../../lib/game/character-sheet/helpers/inventory/actions-interface";
import DangerButton from "../../../../../components/ui/buttons/danger-button";
import LoadingProgressBar from "../../../../../components/ui/progress-bars/loading-progress-bar";
import { AxiosError, AxiosResponse } from "axios";
import Ajax from "../../../../../lib/ajax/ajax";
import SetsInventoryTabProps from "../../../../../lib/game/character-sheet/types/tabs/sets-inventory-tab-props";
import SetsTableState from "../../../../../lib/game/character-sheet/types/tables/sets-table-state";
import SuccessAlert from "../../../../../components/ui/alerts/simple-alerts/success-alert";
import { isEqual } from "lodash";
import WarningAlert from "../../../../../components/ui/alerts/simple-alerts/warning-alert";
import RenameSetModal from "../../modals/rename-set-modal";
import clsx from "clsx";
import UsableItemsDetails from "../../../../../lib/game/character-sheet/types/inventory/usable-items-details";
import InventoryUseDetails from "../../modals/inventory-item-details";
import DangerAlert from "../../../../../components/ui/alerts/simple-alerts/danger-alert";
import PrimaryButton from "../../../../../components/ui/buttons/primary-button";
import { InventoryActionConfirmationType } from "../../../../../components/character-sheet/inventory-action-confirmation-modal/helpers/enums/inventory-action-confirmation-type";
import BaseInventoryActionConfirmationModal from "../../../../../components/character-sheet/inventory-action-confirmation-modal/modals/base-inventory-action-confirmation-modal";
import ModalPropsBuilder from "../../../../../components/character-sheet/inventory-action-confirmation-modal/helpers/modal-props-builder";
import { serviceContainer } from "../../../../../lib/containers/core-container";

export default class SetsTable
    extends React.Component<SetsInventoryTabProps, SetsTableState>
    implements ActionsInterface
{
    private modalPropsBuilder: ModalPropsBuilder;

    constructor(props: SetsInventoryTabProps) {
        super(props);

        this.state = {
            data: [],
            drop_down_labels: [],
            selected_set: null,
            selected_set_index: null,
            loading: false,
            success_message: null,
            show_rename_set: false,
            search_string: "",
            item_id: null,
            view_item: false,
            loading_label: null,
            show_loading_label: false,
            error_message: null,
            selected_slots: [],
            show_action_confirmation_modal: false,
            action_confirmation_type: null,
        };

        this.modalPropsBuilder = serviceContainer().fetch(ModalPropsBuilder);
    }

    componentDidMount() {
        this.setSetData(this.props.sets);
    }

    componentDidUpdate(
        prevProps: Readonly<SetsInventoryTabProps>,
        prevState: Readonly<SetsTableState>,
        snapshot?: any,
    ) {
        if (
            this.state.selected_set !== null &&
            this.state.search_string.length === 0
        ) {
            if (
                !isEqual(
                    this.props.sets[this.state.selected_set].items,
                    this.state.data,
                )
            ) {
                this.setState({
                    data: this.props.sets[this.state.selected_set].items,
                });
            }
        }
    }

    setSetData(sets: {
        [key: string]: {
            equippable: boolean;
            items: InventoryDetails[] | [];
            equipped: boolean;
            is_batch_crafting_set?: boolean;
        };
    }) {
        const setKeys = Object.keys(sets);

        // @ts-ignore
        const data = sets[setKeys[0]].items;

        let setIndex =
            this.state.selected_set_index === null
                ? 0
                : this.state.selected_set_index;
        let selectedSet = "";

        if (this.state.selected_set === null) {
            selectedSet = setKeys[0];
            setIndex = 0;

            for (let i = 0; i < setKeys.length; i++) {
                if (sets[setKeys[i]].equipped) {
                    setIndex = setKeys.findIndex(
                        (setKey) => setKey === setKeys[i],
                    );
                    selectedSet = setKeys[setIndex];
                }
            }
        } else {
            selectedSet = setKeys[setIndex];
        }

        this.setState({
            data: data,
            drop_down_labels: setKeys,
            selected_set: selectedSet,
            selected_set_index: setIndex,
        });
    }

    actions(row: InventoryDetails): JSX.Element {
        return (
            <DangerButton
                button_label={"Remove"}
                on_click={() => this.removeFromSet(row.slot_id)}
                disabled={this.buttonsDisabled()}
            />
        );
    }

    emptySet() {
        // @ts-ignore
        const setId = this.props.sets[this.state.selected_set].set_id;

        this.setState(
            {
                loading: true,
                error_message: null,
                success_message: null,
            },
            () => {
                new Ajax()
                    .setRoute(
                        "character/" +
                            this.props.character_id +
                            "/inventory-set/" +
                            setId +
                            "/remove-all",
                    )
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState(
                                {
                                    loading: false,
                                    success_message: result.data.message,
                                },
                                () => {
                                    this.props.update_inventory(
                                        result.data.inventory,
                                    );
                                },
                            );
                        },
                        (error: AxiosError) => {
                            this.setState({ loading: false });

                            if (typeof error.response !== "undefined") {
                                const response: AxiosResponse = error.response;

                                this.setState({
                                    error_message: response.data.message,
                                });
                            }
                        },
                    );
            },
        );
    }

    equipSet() {
        let setId: any = this.props.savable_sets.filter((set) => {
            return set.name === this.state.selected_set;
        });

        if (setId.length > 0) {
            setId = setId[0].id;
        }

        this.setState(
            {
                loading: true,
                show_loading_label: true,
                loading_label:
                    "Equipping set and recalculating your stats (this can take a few seconds) ...",
                error_message: null,
                success_message: null,
            },
            () => {
                this.props.disable_tabs();

                new Ajax()
                    .setRoute(
                        "character/" +
                            this.props.character_id +
                            "/inventory-set/equip/" +
                            setId,
                    )
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState(
                                {
                                    loading: false,
                                    success_message: result.data.message,
                                },
                                () => {
                                    this.props.update_inventory(
                                        result.data.inventory,
                                    );

                                    this.props.disable_tabs();
                                },
                            );
                        },
                        (error: AxiosError) => {
                            this.setState({ loading: false });

                            if (typeof error.response !== "undefined") {
                                const response: AxiosResponse = error.response;

                                this.setState({
                                    error_message: response.data.message,
                                });
                            }
                        },
                    );
            },
        );
    }

    removeFromSet(id: number) {
        if (this.state.selected_set !== null) {
            const setId = this.props.sets[this.state.selected_set].set_id;

            this.setState(
                {
                    loading: true,
                },
                () => {
                    new Ajax()
                        .setRoute(
                            "character/" +
                                this.props.character_id +
                                "/inventory-set/remove",
                        )
                        .setParameters({
                            inventory_set_id: setId,
                            slot_id: id,
                        })
                        .doAjaxCall(
                            "post",
                            (result: AxiosResponse) => {
                                this.setState(
                                    {
                                        loading: false,
                                        success_message: result.data.message,
                                        search_string: "",
                                    },
                                    () => {
                                        this.props.update_inventory(
                                            result.data.inventory,
                                        );
                                    },
                                );
                            },
                            (error: AxiosError) => {
                                this.setState({ loading: false });

                                if (typeof error.response !== "undefined") {
                                    const response: AxiosResponse =
                                        error.response;

                                    this.setState({
                                        error_message: response.data.message,
                                    });
                                }
                            },
                        );
                },
            );
        }
    }

    renameSet(name: string) {
        const setNames: string[] = Object.keys(this.props.sets);

        const foundName: string[] = setNames.filter((name: string) => {
            return name === this.state.selected_set;
        });

        const setId = this.props.sets[foundName[0]].set_id;

        this.setState(
            {
                loading: true,
                error_message: null,
                success_message: null,
            },
            () => {
                new Ajax()
                    .setRoute(
                        "character/" +
                            this.props.character_id +
                            "/inventory-set/rename-set",
                    )
                    .setParameters({
                        set_id: setId,
                        set_name: name,
                    })
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState(
                                {
                                    loading: false,
                                    success_message: result.data.message,
                                },
                                () => {
                                    this.setSetData(result.data.inventory.sets);

                                    this.props.update_inventory(
                                        result.data.inventory,
                                    );
                                },
                            );
                        },
                        (error: AxiosError) => {
                            if (typeof error.response !== "undefined") {
                                const response: AxiosResponse = error.response;

                                this.setState({
                                    loading: false,
                                    error_message: response.data.message,
                                });
                            }
                        },
                    );
            },
        );
    }

    switchTable(set: string) {
        // @ts-ignore
        const data = this.props.sets[set].items;

        const keys = Object.keys(this.props.sets);

        const index = keys.indexOf(set, 0);

        this.setState({
            data: data,
            selected_set: set,
            selected_set_index: index !== -1 ? index : 0,
            selected_slots: [],
        });
    }

    manageSelectedItems(e: React.ChangeEvent<HTMLInputElement>): void {
        const isChecked = e.target.checked;
        const slotId = parseInt(e.target.dataset.slotId as string, 10) || 0;

        if (slotId <= 0) {
            return;
        }

        const { selected_slots } = this.state;
        let updatedSlots: number[];

        if (selected_slots.length > 0) {
            const duplicateId = selected_slots.indexOf(slotId);

            if (isChecked && duplicateId !== -1) {
                return;
            }

            updatedSlots = isChecked
                ? [...selected_slots, slotId]
                : selected_slots.filter((id) => id !== slotId);
        } else {
            updatedSlots = [slotId];
        }

        this.setState({
            selected_slots: updatedSlots,
        });
    }

    selectAllSlots() {
        this.setState({
            selected_slots: this.state.data.map((slot) => slot.slot_id),
        });
    }

    resetSelectedSlots() {
        this.setState({
            selected_slots: [],
        });
    }

    isSelectedSetBatchCraftingSet(): boolean {
        if (this.state.selected_set === null) {
            return false;
        }

        return (
            this.props.sets[this.state.selected_set]?.is_batch_crafting_set ??
            false
        );
    }

    manageConfirmationModal(type?: InventoryActionConfirmationType) {
        let actionConfirmationType = null;

        if (!this.state.show_action_confirmation_modal && type) {
            actionConfirmationType = type;
        }

        this.setState({
            show_action_confirmation_modal:
                !this.state.show_action_confirmation_modal,
            action_confirmation_type: actionConfirmationType,
        });
    }

    buildSelectedItemsDropDown() {
        if (this.isSelectedSetBatchCraftingSet()) {
            return [
                {
                    name: "Destroy Selected",
                    icon_class: "fas fa-trash",
                    on_click: () =>
                        this.manageConfirmationModal(
                            InventoryActionConfirmationType.DESTROY_SELECTED_FROM_SET,
                        ),
                },
            ];
        }

        return [
            {
                name: "Sell Selected",
                icon_class: "far fa-money-bill-alt",
                on_click: () =>
                    this.manageConfirmationModal(
                        InventoryActionConfirmationType.SELL_SELECTED_FROM_SET,
                    ),
            },
            {
                name: "Disenchant Selected",
                icon_class: "ra ra-fire",
                on_click: () =>
                    this.manageConfirmationModal(
                        InventoryActionConfirmationType.DISENCHANT_SELECTED_FROM_SET,
                    ),
            },
        ];
    }

    getSelectedSlotItemNames(): string[] {
        return this.state.data
            .filter((slot) => this.state.selected_slots.includes(slot.slot_id))
            .map((slot) => slot.item_name);
    }

    buildMenuItems() {
        return this.state.drop_down_labels.map((label: string) => {
            const isBatchCraftingSet =
                this.props.sets[label]?.is_batch_crafting_set ?? false;

            return {
                name: label,
                icon_class: isBatchCraftingSet
                    ? "ra ra-anvil"
                    : clsx("ra ra-crossed-swords", {
                          "text-yellow-600": this.cannotEquipSet(label),
                      }),
                extra_class: isBatchCraftingSet
                    ? "border border-yellow-sea-700 bg-yellow-sea-500 text-yellow-sea-950 dark:border-yellow-sea-300 dark:bg-yellow-sea-800 dark:text-yellow-sea-50 font-medium"
                    : undefined,
                on_click: () => this.switchTable(label),
            };
        });
    }

    manageRenameSet() {
        this.setState({
            show_rename_set: !this.state.show_rename_set,
        });
    }

    buildActionsDropDown() {
        const actions = [];
        const selectedSet =
            this.state.selected_set !== null
                ? this.props.sets[this.state.selected_set]
                : null;

        if (!selectedSet?.is_batch_crafting_set) {
            actions.push({
                name: "Rename set",
                icon_class: "fas fa-edit",
                on_click: () => this.manageRenameSet(),
            });
        }

        if (this.state.selected_set !== null && selectedSet !== null) {
            if (
                this.state.selected_set !== this.props.set_name_equipped &&
                selectedSet.items.length > 0
            ) {
                if (selectedSet.can_empty) {
                    actions.push({
                        name: "Empty set",
                        icon_class: "fas fa-eraser",
                        on_click: () => this.emptySet(),
                    });
                }

                if (
                    !selectedSet.is_batch_crafting_set &&
                    !this.cannotEquipSet() &&
                    !this.props.is_automation_running
                ) {
                    actions.push({
                        name: "Equip set",
                        icon_class: "ra ra-muscle-fat",
                        on_click: () => this.equipSet(),
                    });
                }
            }
        }

        return actions;
    }

    search(e: React.ChangeEvent<HTMLInputElement>) {
        const value = e.target.value;

        // @ts-ignore
        const data = this.props.sets[this.state.selected_set].items.filter(
            (item: InventoryDetails) => {
                return (
                    item.item_name.includes(value) || item.type.includes(value)
                );
            },
        );

        this.setState({
            data: data,
            search_string: value,
        });
    }

    buttonsDisabled() {
        if (this.state.selected_set !== null) {
            return (
                this.props.sets[this.state.selected_set].equipped ||
                this.props.is_dead ||
                this.props.is_automation_running ||
                this.state.loading
            );
        }

        return true;
    }

    cannotEquipSet(setName?: string) {
        if (this.state.selected_set !== null) {
            if (typeof setName !== "undefined") {
                return !this.props.sets[setName].equippable;
            }

            return !this.props.sets[this.state.selected_set].equippable;
        }

        return false;
    }

    clearSuccessMessage() {
        this.setState({
            success_message: null,
        });
    }

    clearErrorMessage() {
        this.setState({
            error_message: null,
        });
    }

    buildSetTitle() {
        if (this.state.selected_set !== null) {
            return "Viewing: " + this.state.selected_set + ".";
        }

        return null;
    }

    viewItem(item?: InventoryDetails | UsableItemsDetails) {
        this.setState({
            item_id: typeof item !== "undefined" ? item.item_id : null,
            view_item: !this.state.view_item,
        });
    }

    render() {
        return (
            <Fragment>
                {this.state.success_message !== null ? (
                    <SuccessAlert
                        close_alert={this.clearSuccessMessage.bind(this)}
                        additional_css={"mt-4 mb-4"}
                    >
                        {this.state.success_message}
                    </SuccessAlert>
                ) : null}
                {this.state.error_message !== null ? (
                    <DangerAlert
                        close_alert={this.clearErrorMessage.bind(this)}
                        additional_css={"mt-4 mb-4"}
                    >
                        {this.state.error_message}
                    </DangerAlert>
                ) : null}
                {this.cannotEquipSet() ? (
                    <WarningAlert additional_css={"mb-4"}>
                        Cannot equip set because it violates the{" "}
                        <a href={"/information/equipment-sets"} target="_blank">
                            set <i className="fas fa-external-link-alt"></i>
                        </a>{" "}
                        rules. You can still treat this set like a stash tab.
                    </WarningAlert>
                ) : null}
                {this.state.selected_set !== null &&
                !this.props.sets[this.state.selected_set].can_empty &&
                this.props.sets[this.state.selected_set]
                    .empty_disabled_reason ? (
                    <WarningAlert additional_css={"mb-4"}>
                        {
                            this.props.sets[this.state.selected_set]
                                .empty_disabled_reason
                        }
                    </WarningAlert>
                ) : null}
                {this.buildSetTitle() !== null ? (
                    <div>
                        <h4 className="text-orange-500 dark:text-orange-400">
                            {this.buildSetTitle()}
                        </h4>
                    </div>
                ) : null}
                <div className="flex flex-col md:flex-row flex-wrap items-center w-full md:space-x-4">
                    <div className="w-full md:w-auto">
                        <DropDown
                            menu_items={this.buildMenuItems()}
                            button_title="Sets"
                            selected_name={this.state.selected_set}
                            secondary_selected={this.props.set_name_equipped}
                            disabled={this.props.is_dead || this.state.loading}
                        />
                    </div>
                    {!this.isSelectedSetBatchCraftingSet() ? (
                        <div className="w-full md:w-auto mt-[-10px] md:mt-0">
                            <DropDown
                                menu_items={this.buildActionsDropDown()}
                                button_title="Actions"
                                disabled={
                                    this.props.is_dead || this.state.loading
                                }
                            />
                        </div>
                    ) : null}
                    {this.isSelectedSetBatchCraftingSet() &&
                    this.state.data.length > 0 ? (
                        <div className="w-full md:w-auto mt-[-10px] md:mt-0">
                            {this.state.selected_slots.length > 0 ? (
                                <DangerButton
                                    button_label={"Deselect all items"}
                                    on_click={this.resetSelectedSlots.bind(
                                        this,
                                    )}
                                    additional_css="w-full md:w-auto"
                                    disabled={
                                        this.props.is_dead || this.state.loading
                                    }
                                />
                            ) : (
                                <PrimaryButton
                                    button_label={"Select all items"}
                                    on_click={this.selectAllSlots.bind(this)}
                                    additional_css="w-full md:w-auto"
                                    disabled={
                                        this.props.is_dead || this.state.loading
                                    }
                                />
                            )}
                        </div>
                    ) : null}
                    {this.isSelectedSetBatchCraftingSet() &&
                    this.state.data.length > 0 ? (
                        <div className="w-full md:w-auto mt-[-10px] md:mt-0">
                            <DangerButton
                                button_label={"Destroy All"}
                                on_click={() =>
                                    this.manageConfirmationModal(
                                        InventoryActionConfirmationType.DESTROY_ALL_FROM_SET,
                                    )
                                }
                                additional_css="w-full md:w-auto"
                                disabled={
                                    this.props.is_dead || this.state.loading
                                }
                            />
                        </div>
                    ) : null}
                    {this.isSelectedSetBatchCraftingSet() &&
                    this.state.selected_slots.length > 0 ? (
                        <div className="w-full md:w-auto mt-[-10px] md:mt-0">
                            <DropDown
                                menu_items={this.buildSelectedItemsDropDown()}
                                button_title="Selected Items (Actions)"
                                disabled={
                                    this.props.is_dead || this.state.loading
                                }
                                greenButton={true}
                            />
                        </div>
                    ) : null}
                    <div className="w-full md:w-auto md:absolute md:right-[10px]">
                        <input
                            type="text"
                            name="search"
                            className="form-control w-full md:w-auto"
                            onChange={this.search.bind(this)}
                            placeholder="Search"
                            value={this.state.search_string}
                        />
                    </div>
                </div>

                {this.state.loading ? (
                    <LoadingProgressBar
                        show_label={this.state.show_loading_label}
                        label={this.state.loading_label}
                    />
                ) : null}

                {this.state.show_rename_set &&
                this.state.selected_set !== null ? (
                    <RenameSetModal
                        is_open={this.state.show_rename_set}
                        manage_modal={this.manageRenameSet.bind(this)}
                        title={"Rename Set"}
                        current_set_name={this.state.selected_set}
                        rename_set={this.renameSet.bind(this)}
                    />
                ) : null}

                {this.state.view_item && this.state.item_id !== null ? (
                    <InventoryUseDetails
                        character_id={this.props.character_id}
                        item_id={this.state.item_id}
                        is_open={this.state.view_item}
                        manage_modal={this.viewItem.bind(this)}
                    />
                ) : null}

                <div className={"max-w-full overflow-y-hidden"}>
                    <Table
                        data={this.state.data}
                        columns={BuildInventoryTableColumns(
                            this.props.view_port,
                            this,
                            this.viewItem.bind(this),
                            this.props.manage_skills,
                            undefined,
                            this.isSelectedSetBatchCraftingSet()
                                ? this.manageSelectedItems.bind(this)
                                : undefined,
                            this.state.selected_slots,
                        )}
                        dark_table={this.props.dark_tables}
                    />
                </div>

                {this.state.show_action_confirmation_modal &&
                this.state.action_confirmation_type !== null &&
                this.state.selected_set !== null ? (
                    <BaseInventoryActionConfirmationModal
                        type={this.state.action_confirmation_type}
                        is_open={this.state.show_action_confirmation_modal}
                        manage_modal={this.manageConfirmationModal.bind(this)}
                        title={this.modalPropsBuilder
                            .setActionType(this.state.action_confirmation_type)
                            .fetchModalName()}
                        update_inventory={this.props.update_inventory}
                        set_success_message={(message: string) =>
                            this.setState({ success_message: message })
                        }
                        selected_item_names={this.getSelectedSlotItemNames()}
                        reset_selected_items={this.resetSelectedSlots.bind(
                            this,
                        )}
                        data={{
                            url: this.modalPropsBuilder
                                .setActionType(
                                    this.state.action_confirmation_type,
                                )
                                .fetchActionUrl(this.props.character_id),
                            params: {
                                set_id: this.props.sets[this.state.selected_set]
                                    .set_id,
                                slot_ids: this.state.selected_slots,
                            },
                        }}
                        usable_sets={this.props.savable_sets}
                    />
                ) : null}
            </Fragment>
        );
    }
}
