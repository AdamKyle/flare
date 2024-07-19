import React, { Fragment } from "react";
import { formatNumber } from "../../../../../lib/game/format-number";
import KingdomTopSectionProps from "../../../../../sections/map/types/map/kingdom-pins/modals/components/kingdom-top-section-props";

export default class KingdomTopSection extends React.Component<
    KingdomTopSectionProps,
    {}
> {
    constructor(props: KingdomTopSectionProps) {
        super(props);
    }

    render() {
        return (
            <Fragment>
                <div className={"w-full lg:grid lg:grid-cols-3 lg:gap-2"}>
                    <div>
                        <dl>
                            <dt>Wood:</dt>
                            <dd>
                                {formatNumber(this.props.kingdom.current_wood)}{" "}
                                / {formatNumber(this.props.kingdom.max_wood)}
                            </dd>
                            <dt>Clay:</dt>
                            <dd>
                                {formatNumber(this.props.kingdom.current_clay)}{" "}
                                / {formatNumber(this.props.kingdom.max_clay)}
                            </dd>
                        </dl>
                    </div>
                    <div>
                        <dl>
                            <dt>Stone:</dt>
                            <dd>
                                {formatNumber(this.props.kingdom.current_stone)}{" "}
                                / {formatNumber(this.props.kingdom.max_stone)}
                            </dd>
                            <dt>Iron:</dt>
                            <dd>
                                {formatNumber(this.props.kingdom.current_iron)}{" "}
                                / {formatNumber(this.props.kingdom.max_iron)}
                            </dd>
                        </dl>
                    </div>
                    <div>
                        <dl>
                            <dt>Pop.:</dt>
                            <dd>
                                {formatNumber(
                                    this.props.kingdom.current_population,
                                )}{" "}
                                /{" "}
                                {formatNumber(
                                    this.props.kingdom.max_population,
                                )}
                            </dd>
                        </dl>
                    </div>
                </div>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
            </Fragment>
        );
    }
}
