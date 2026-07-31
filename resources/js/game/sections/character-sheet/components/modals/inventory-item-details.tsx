import React, { Fragment } from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import ItemNameColorationText from "../../../../components/items/item-name/item-name-coloration-text";
import { AxiosError, AxiosResponse } from "axios";
import Ajax from "../../../../lib/ajax/ajax";
import ComponentLoading from "../../../../components/ui/loading/component-loading";
import ItemDetails from "./components/item-details";
import InventoryQuestItemDetails from "./components/inventory-quest-item-details";
import QuestItem from "../../../../components/modals/item-details/item-views/quest-item";
import InventoryDetails from "../../../../lib/game/character-sheet/types/inventory/inventory-details";

interface InventoryUseDetailsProps {
    is_open: boolean;
    manage_modal: () => void;
    character_id: number;
    item_id?: number;
    slot_id?: number;
    preloaded_item?: InventoryDetails;
}

interface InventoryUseDetailsState {
    loading: boolean;
    item: InventoryDetails | null;
    error_message: string | null;
}

export default class InventoryUseDetails extends React.Component<
    InventoryUseDetailsProps,
    InventoryUseDetailsState
> {
    constructor(props: InventoryUseDetailsProps) {
        super(props);

        this.state = {
            loading: typeof props.preloaded_item === "undefined",
            item: props.preloaded_item ?? null,
            error_message: null,
        };
    }

    componentDidMount() {
        if (typeof this.props.preloaded_item !== "undefined") {
            return;
        }

        new Ajax()
            .setRoute(
                "character/" +
                    this.props.character_id +
                    "/inventory/item/" +
                    this.props.item_id,
            )
            .setParameters(
                typeof this.props.slot_id === "number"
                    ? { slot_id: this.props.slot_id }
                    : {},
            )
            .doAjaxCall(
                "get",
                (result: AxiosResponse) => {
                    this.setState({
                        loading: false,
                        item: result.data,
                    });
                },
                (error: AxiosError) => {
                    if (typeof error.response !== "undefined") {
                        const response: AxiosResponse = error.response;

                        this.setState({
                            loading: false,
                            error_message: response.data.hasOwnProperty(
                                "message",
                            )
                                ? response.data.message
                                : response.data.error,
                        });
                    }
                },
            );
    }

    modalTitle() {
        if (this.state.loading) {
            return "Fetching item details ...";
        }

        if (this.state.error_message !== null) {
            return "There was an error!";
        }

        return (
            <ItemNameColorationText
                custom_width={false}
                item={this.state.item}
            />
        );
    }

    largeModal() {
        if (this.state.item !== null) {
            return this.state.item.type !== "quest";
        }

        return false;
    }

    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.manage_modal}
                title={this.modalTitle()}
                large_modal={this.largeModal()}
                additional_dialogue_css={"top-[110px]"}
            >
                <div className="mb-5 relative">
                    {this.state.loading ? (
                        <div className="py-10">
                            <ComponentLoading />
                        </div>
                    ) : this.state.error_message !== null ? (
                        <Fragment>
                            <p className="my-4 text-red-500 dark:text-red-400">
                                {this.state.error_message}
                            </p>
                        </Fragment>
                    ) : (
                        <Fragment>
                            {this.state.item.type === "quest" ? (
                                <QuestItem item={this.state.item} />
                            ) : (
                                <ItemDetails
                                    item={this.state.item}
                                    character_id={this.props.character_id}
                                    preloaded_attached_gems={
                                        this.props.preloaded_item?.sockets
                                    }
                                />
                            )}
                        </Fragment>
                    )}
                </div>
            </Dialogue>
        );
    }
}
