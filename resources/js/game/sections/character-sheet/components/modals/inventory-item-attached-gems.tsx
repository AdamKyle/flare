import React from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import { AxiosError, AxiosResponse } from "axios";
import Ajax from "../../../../lib/ajax/ajax";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import GemBagSlotDetails from "../../../../lib/game/character-sheet/types/inventory/gem-bag-slot-details";
import BasicCard from "../../../../components/ui/cards/basic-card";

interface InventoryItemAttachedGemsProps {
    is_open: boolean;
    manage_modal: () => void;
    character_id: number;
    item_id: number;
    preloaded_attached_gems?: GemBagSlotDetails[];
}

interface InventoryItemAttachedGemsState {
    loading: boolean;
    attached_gems: GemBagSlotDetails[];
    error_message: string | null;
}

export default class InventoryItemAttachedGems extends React.Component<
    InventoryItemAttachedGemsProps,
    InventoryItemAttachedGemsState
> {
    constructor(props: InventoryItemAttachedGemsProps) {
        super(props);

        this.state = {
            loading: typeof props.preloaded_attached_gems === "undefined",
            attached_gems: props.preloaded_attached_gems ?? [],
            error_message: null,
        };
    }

    componentDidMount() {
        if (typeof this.props.preloaded_attached_gems !== "undefined") {
            return;
        }

        new Ajax()
            .setRoute(
                "socketed-gems/" +
                    this.props.character_id +
                    "/" +
                    this.props.item_id,
            )
            .doAjaxCall(
                "get",
                (result: AxiosResponse) => {
                    this.setState({
                        attached_gems: result.data.socketed_gems,
                        loading: false,
                    });
                },
                (error: AxiosError) => {
                    this.setState({
                        loading: false,
                        error_message:
                            error.response?.data?.message ??
                            "Unable to load attached gems.",
                    });
                },
            );
    }

    renderGems() {
        return this.state.attached_gems.map((gem: GemBagSlotDetails) => {
            return (
                <BasicCard additionalClasses="my-4">
                    <h3 className="my-4 text-lime-600 dark:text-lime-500">
                        {gem.name}
                    </h3>
                    <dl>
                        <dt>Tier</dt>
                        <dd>{gem.tier}</dd>
                        <dt>{gem.primary_atonement_name + " Atonement: "}</dt>
                        <dd>
                            {(gem.primary_atonement_amount * 100).toFixed(0)}%
                        </dd>
                        <dt>{gem.secondary_atonement_name + " Atonement: "}</dt>
                        <dd>
                            {(gem.secondary_atonement_amount * 100).toFixed(0)}%
                        </dd>
                        <dt>{gem.tertiary_atonement_name + " Atonement: "}</dt>
                        <dd>
                            {(gem.tertiary_atonement_amount * 100).toFixed(0)}%
                        </dd>
                    </dl>
                </BasicCard>
            );
        });
    }

    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.manage_modal}
                title={"Attached Gems"}
                primary_button_disabled={this.state.loading}
            >
                {this.state.loading ? (
                    <LoadingProgressBar />
                ) : this.state.error_message !== null ? (
                    <p className="my-4 text-red-500 dark:text-red-400">
                        {this.state.error_message}
                    </p>
                ) : this.state.attached_gems.length > 0 ? (
                    <div className="max-h-[350px] overflow-y-scroll">
                        {this.renderGems()}
                    </div>
                ) : (
                    <p className={"my-4"}>No Attached Gems</p>
                )}
            </Dialogue>
        );
    }
}
