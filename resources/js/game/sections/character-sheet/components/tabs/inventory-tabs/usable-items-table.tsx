import React, { Fragment, ReactNode } from "react";
import { Menu, Transition } from "@headlessui/react";
import clsx from "clsx";
import Table from "../../../../../components/ui/data-tables/table";
import { buildLimitedColumns } from "../../../../../lib/game/character-sheet/helpers/inventory/build-inventory-table-columns";
import ActionsInterface from "../../../../../lib/game/character-sheet/helpers/inventory/actions-interface";
import InventoryDetails from "../../../../../lib/game/character-sheet/types/inventory/inventory-details";
import UsableItemTable from "../../../../../lib/game/character-sheet/types/tables/usable-items-table-props";
import ListItemModal from "../../../../../components/modals/item-details/action-modals/list-item-modal";
import Ajax from "../../../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import InventoryUseItem from "../../modals/inventory-use-item";
import UsableItemsDetails from "../../../../../lib/game/character-sheet/types/inventory/usable-items-details";
import InventoryUseDetails from "../../modals/inventory-use-details";
import InventoryActionConfirmationModal from "../../modals/inventory-action-confirmation-modal";
import DangerAlert from "../../../../../components/ui/alerts/simple-alerts/danger-alert";

export default class UsableItemsTable
    extends React.Component<UsableItemTable, any>
    implements ActionsInterface
{
    constructor(props: UsableItemTable) {
        super(props);

        this.state = {
            show_list_modal: false,
            show_use_item_modal: false,
            show_usable_details: false,
            show_use_many: false,
            show_destroy_item: false,
            item_to_list: null,
            item_to_use: null,
            item_to_destroy_name: null,
            item_slot_id_to_delete: null,
            using_all_slot_id: null,
            error_message: null,
        };
    }

    manageUseItem(row: UsableItemsDetails) {
        this.setState({
            show_use_item_modal: !this.state.show_use_item_modal,
            item_to_use: row,
        });
    }

    list(listedFor: number) {
        new Ajax()
            .setRoute("market-board/sell-item/" + this.props.character_id)
            .setParameters({
                list_for: listedFor,
                slot_id: this.state.item_to_list?.slot_id,
            })
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    this.setState(
                        {
                            item_to_list: null,
                        },
                        () => {
                            this.props.update_inventory(result.data.inventory);

                            this.props.set_success_message(result.data.message);
                        },
                    );
                },
                (error: AxiosError) => {},
            );
    }

    destroy(row: UsableItemsDetails) {
        this.setState({
            show_destroy_item: !this.state.show_destroy_item,
            item_to_destroy_name: row.item_name,
            item_slot_id_to_delete: row.slot_id,
        });
    }

    showDestroyConfirmation() {
        this.setState({
            show_destroy_item: !this.state.show_destroy_item,
            item_to_destroy_name: null,
            item_slot_id_to_delete: null,
        });
    }

    manageList(item?: UsableItemsDetails) {
        this.setState({
            show_list_modal: !this.state.show_list_modal,
            item_to_list: typeof item !== "undefined" ? item : null,
        });
    }

    actions(row: UsableItemsDetails): ReactNode {
        const usingAll = this.state.using_all_slot_id === row.slot_id;
        const anyUsingAll = this.state.using_all_slot_id !== null;

        return (
            <Menu as="div" className="relative inline-block text-left w-full">
                <Menu.Button
                    className="inline-flex justify-center w-full whitespace-nowrap px-4 py-2 text-sm font-medium rounded-small focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-200 dark:focus-visible:ring-white focus-visible:ring-opacity-75 hover:bg-blue-700 hover:drop-shadow-md dark:text-white hover:text-gray-300 bg-blue-600 dark:bg-blue-700 text-white dark:hover:bg-blue-600 dark:hover:text-white font-semibold rounded-sm drop-shadow-sm disabled:bg-blue-400 dark:disabled:bg-blue-400"
                    disabled={anyUsingAll}
                >
                    {usingAll ? (
                        <i
                            className="fas fa-spinner fa-pulse w-5 h-5"
                            aria-hidden="true"
                        ></i>
                    ) : (
                        <Fragment>
                            Actions
                            <i
                                className="fas fa-chevron-down w-5 h-5 ml-2 -mr-1 text-white mt-1"
                                aria-hidden="true"
                            ></i>
                        </Fragment>
                    )}
                </Menu.Button>
                <Transition
                    as={Fragment}
                    enter="transition ease-out duration-100"
                    enterFrom="transform opacity-0 scale-95"
                    enterTo="transform opacity-100 scale-100"
                    leave="transition ease-in duration-75"
                    leaveFrom="transform opacity-100 scale-100"
                    leaveTo="transform opacity-0 scale-95"
                >
                    <Menu.Items
                        portal
                        anchor="bottom end"
                        className="z-[100] w-40 mt-2 origin-top-right dark:bg-gray-700 bg-white divide-y dark:divide-gray-600 divide-gray-300 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none max-h-64 overflow-y-auto"
                    >
                        <Menu.Item disabled={anyUsingAll}>
                            {({ active }) => (
                                <button
                                    className={clsx(
                                        "group flex rounded-sm items-center w-full py-2 px-4 text-sm md:whitespace-nowrap",
                                        {
                                            "bg-blue-500 dark:bg-blue-600 text-white":
                                                active && !anyUsingAll,
                                            "text-gray-900 dark:text-white":
                                                !anyUsingAll,
                                            "text-gray-400 dark:text-gray-500 cursor-not-allowed":
                                                anyUsingAll,
                                        },
                                    )}
                                    onClick={() => this.manageList(row)}
                                    disabled={anyUsingAll}
                                >
                                    List
                                </button>
                            )}
                        </Menu.Item>
                        <Menu.Item disabled={anyUsingAll}>
                            {({ active }) => (
                                <button
                                    className={clsx(
                                        "group flex rounded-sm items-center w-full py-2 px-4 text-sm md:whitespace-nowrap",
                                        {
                                            "bg-blue-500 dark:bg-blue-600 text-white":
                                                active && !anyUsingAll,
                                            "text-gray-900 dark:text-white":
                                                !anyUsingAll,
                                            "text-gray-400 dark:text-gray-500 cursor-not-allowed":
                                                anyUsingAll,
                                        },
                                    )}
                                    onClick={() => this.destroy(row)}
                                    disabled={anyUsingAll}
                                >
                                    Destroy
                                </button>
                            )}
                        </Menu.Item>
                        {row.usable && !row.damages_kingdoms ? (
                            <Fragment>
                                <Menu.Item disabled={anyUsingAll}>
                                    {({ active }) => (
                                        <button
                                            className={clsx(
                                                "group flex rounded-sm items-center w-full py-2 px-4 text-sm md:whitespace-nowrap",
                                                {
                                                    "bg-blue-500 dark:bg-blue-600 text-white":
                                                        active && !anyUsingAll,
                                                    "text-gray-900 dark:text-white":
                                                        !anyUsingAll,
                                                    "text-gray-400 dark:text-gray-500 cursor-not-allowed":
                                                        anyUsingAll,
                                                },
                                            )}
                                            onClick={() =>
                                                this.manageUseItem(row)
                                            }
                                            disabled={anyUsingAll}
                                        >
                                            Use
                                        </button>
                                    )}
                                </Menu.Item>
                                <Menu.Item
                                    disabled={!row.can_stack || anyUsingAll}
                                >
                                    {({ active }) => (
                                        <button
                                            className={clsx(
                                                "group flex rounded-sm items-center w-full py-2 px-4 text-sm md:whitespace-nowrap",
                                                {
                                                    "bg-blue-500 dark:bg-blue-600 text-white":
                                                        active &&
                                                        row.can_stack &&
                                                        !anyUsingAll,
                                                    "text-gray-900 dark:text-white":
                                                        row.can_stack &&
                                                        !anyUsingAll,
                                                    "text-gray-400 dark:text-gray-500 cursor-not-allowed":
                                                        !row.can_stack ||
                                                        anyUsingAll,
                                                },
                                            )}
                                            onClick={this.props.manage_use_many}
                                            disabled={
                                                !row.can_stack || anyUsingAll
                                            }
                                        >
                                            Use Many
                                        </button>
                                    )}
                                </Menu.Item>
                                <Menu.Item
                                    disabled={!row.can_stack || anyUsingAll}
                                >
                                    {({ active, close }) => (
                                        <button
                                            className={clsx(
                                                "group flex rounded-sm items-center w-full py-2 px-4 text-sm md:whitespace-nowrap",
                                                {
                                                    "bg-blue-500 dark:bg-blue-600 text-white":
                                                        active &&
                                                        row.can_stack &&
                                                        !anyUsingAll,
                                                    "text-gray-900 dark:text-white":
                                                        row.can_stack &&
                                                        !anyUsingAll,
                                                    "text-gray-400 dark:text-gray-500 cursor-not-allowed":
                                                        !row.can_stack ||
                                                        anyUsingAll,
                                                },
                                            )}
                                            onClick={() => {
                                                close();
                                                this.useAll(row);
                                            }}
                                            disabled={
                                                !row.can_stack || anyUsingAll
                                            }
                                        >
                                            Use All
                                        </button>
                                    )}
                                </Menu.Item>
                            </Fragment>
                        ) : null}
                    </Menu.Items>
                </Transition>
            </Menu>
        );
    }

    useAll(row: UsableItemsDetails) {
        this.setState({
            using_all_slot_id: row.slot_id,
            error_message: null,
        });

        new Ajax()
            .setRoute(
                "character/" +
                    this.props.character_id +
                    "/inventory/use-alchemy-item/" +
                    row.slot_id,
            )
            .setParameters({ use_all: true })
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    this.setState({ using_all_slot_id: null });
                    this.props.update_inventory(result.data.inventory);
                    this.props.set_success_message(result.data.message);
                },
                (error: AxiosError) => {
                    this.setState({ using_all_slot_id: null });

                    if (typeof error.response !== "undefined") {
                        const response: AxiosResponse = error.response;

                        this.setState({
                            error_message:
                                response.data.message ?? response.data.error,
                        });
                    }
                },
            );
    }

    manageViewItem(item?: InventoryDetails | UsableItemsDetails) {
        this.setState({
            show_usable_details: !this.state.show_usable_details,
            item_to_use: typeof item !== "undefined" ? item : null,
        });
    }

    render() {
        return (
            <Fragment>
                {this.state.error_message !== null ? (
                    <DangerAlert additional_css={"my-4"}>
                        {this.state.error_message}
                    </DangerAlert>
                ) : null}
                <div className={"max-w-full"}>
                    <Table
                        data={this.props.usable_items}
                        columns={buildLimitedColumns(
                            this.props.view_port,
                            this,
                            this.manageViewItem.bind(this),
                            true,
                        )}
                        dark_table={this.props.dark_table}
                    />
                </div>

                {this.state.show_destroy_item ? (
                    <InventoryActionConfirmationModal
                        is_large_modal={false}
                        is_open={this.state.show_destroy_item}
                        manage_modal={this.showDestroyConfirmation.bind(this)}
                        title={"Destroy " + this.state.item_to_destroy_name}
                        url={
                            "character/" +
                            this.props.character_id +
                            "/inventory/destroy-alchemy-item"
                        }
                        ajax_params={{
                            slot_id: this.state.item_slot_id_to_delete,
                        }}
                        update_inventory={this.props.update_inventory}
                        set_success_message={this.props.set_success_message}
                    >
                        <p>
                            Are you sure you want to do this? This action will
                            destroy all items in this stack from your Alchemy
                            Bag. You cannot undo this action.
                        </p>
                    </InventoryActionConfirmationModal>
                ) : null}

                {this.state.show_list_modal &&
                this.state.item_to_list !== null ? (
                    <ListItemModal
                        is_open={this.state.show_list_modal}
                        manage_modal={this.manageList.bind(this)}
                        list_item={this.list.bind(this)}
                        item={this.state.item_to_list}
                        dark_charts={this.props.dark_table}
                    />
                ) : null}

                {this.state.show_use_item_modal &&
                this.state.item_to_use !== null ? (
                    <InventoryUseItem
                        is_open={this.state.show_use_item_modal}
                        manage_modal={this.manageUseItem.bind(this)}
                        item={this.state.item_to_use}
                        update_inventory={this.props.update_inventory}
                        set_success_message={this.props.set_success_message}
                        character_id={this.props.character_id}
                    />
                ) : null}

                {this.state.show_usable_details &&
                this.state.item_to_use !== null ? (
                    <InventoryUseDetails
                        is_open={this.state.show_usable_details}
                        manage_modal={this.manageViewItem.bind(this)}
                        item={this.state.item_to_use}
                    />
                ) : null}
            </Fragment>
        );
    }
}
