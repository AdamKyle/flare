import React from "react";
import clsx from "clsx";
import GemComparisonProps from "./types/gem-comparison-props";

export default class GemComparisonDetails extends React.Component<
    GemComparisonProps,
    {}
> {
    constructor(props: GemComparisonProps) {
        super(props);
    }

    render() {
        return (
            <dl>
                <dt>Tier</dt>
                <dd>{this.props.gem.tier}</dd>
                <dt>{this.props.gem.primary_atonement_type}</dt>
                <dl
                    className={clsx({
                        "text-green-700 dark:text-green-500":
                            this.props.gem.primary_atonement_amount > 0,
                        "text-red-700 dark:text-red-500":
                            this.props.gem.primary_atonement_amount < 0,
                    })}
                >
                    {this.props.gem.primary_atonement_amount > 0 ? "+" : ""}
                    {(this.props.gem.primary_atonement_amount * 100).toFixed(2)}
                    %
                </dl>
                <dt>{this.props.gem.secondary_atonement_type}</dt>
                <dl
                    className={clsx({
                        "text-green-700 dark:text-green-500":
                            this.props.gem.secondary_atonement_amount > 0,
                        "text-red-700 dark:text-red-500":
                            this.props.gem.secondary_atonement_amount < 0,
                    })}
                >
                    {this.props.gem.secondary_atonement_amount > 0 ? "+" : ""}
                    {(this.props.gem.secondary_atonement_amount * 100).toFixed(
                        2,
                    )}
                    %
                </dl>
                <dt>{this.props.gem.tertiary_atonement_type}</dt>
                <dl
                    className={clsx({
                        "text-green-700 dark:text-green-500":
                            this.props.gem.tertiary_atonement_amount > 0,
                        "text-red-700 dark:text-red-500":
                            this.props.gem.tertiary_atonement_amount < 0,
                    })}
                >
                    {this.props.gem.tertiary_atonement_amount > 0 ? "+" : ""}
                    {(this.props.gem.tertiary_atonement_amount * 100).toFixed(
                        2,
                    )}
                    %
                </dl>
            </dl>
        );
    }
}
