import React from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import { AxiosError, AxiosResponse } from "axios";
import Ajax from "../../../../lib/ajax/ajax";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import GemBagSlotDetails from "../../../../lib/game/character-sheet/types/inventory/gem-bag-slot-details";
import BasicCard from "../../../../components/ui/cards/basic-card";

export default class InventoryItemAttachedGems extends React.Component<
    any,
    any
> {
    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            attached_gems: [],
        };
    }

    componentDidMount() {
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
                    console.error(error);
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
