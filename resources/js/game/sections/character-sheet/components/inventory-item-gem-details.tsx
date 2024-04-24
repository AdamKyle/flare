import React, { Fragment } from "react";
import BasicCard from "../../../components/ui/cards/basic-card";
import RenderAtonementDetails from "../../components/gems/components/render-atonement-details";
import clsx from "clsx";

export default class InventoryItemGemDetails extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    atonementChanges(
        originalAtonement: any,
        equippedAtonement: any,
    ): JSX.Element[] | [] {
        const atonements = Object.entries(originalAtonement.atonements).map(
            ([key, value]) => ({ [key]: value }),
        );
        const equippedAtonements = Object.entries(equippedAtonement).map(
            ([key, value]) => ({ [key]: value }),
        );

        const elements: JSX.Element[] = [];

        atonements.forEach((atonement: any) => {
            const atonementName = Object.keys(atonement)[0];
            const atonementValue = atonement[atonementName];

            const value = this.findAtonementForChange(
                equippedAtonements,
                atonementName,
            );

            const total = parseFloat(atonementValue);

            if (typeof total === "undefined") {
                elements.push(
                    <Fragment>
                        <dt>{atonementName}</dt>
                        <dd className="text-green-700 dark:text-green-500">
                            +{(total * 100).toFixed(0)}%
                        </dd>
                    </Fragment>,
                );
            }

            if (total > value) {
                elements.push(
                    <Fragment>
                        <dt>{atonementName}</dt>
                        <dd className="text-green-700 dark:text-green-500">
                            +
                            {(value === 0
                                ? total * 100
                                : (total - value) * 100
                            ).toFixed(0)}
                            %
                        </dd>
                    </Fragment>,
                );
            }

            if (value < total) {
                elements.push(
                    <Fragment>
                        <dt>{atonementName}</dt>
                        <dd className="text-red-700 dark:text-red-500">
                            -{((total - value) * 100).toFixed(0)}%
                        </dd>
                    </Fragment>,
                );
            }

            elements.push(
                <Fragment>
                    <dt>{atonementName}</dt>
                    <dd>{(total * 100).toFixed(0)}%</dd>
                </Fragment>,
            );
        });

        return elements;
    }

    findAtonementForChange(
        equippedAtonements: any,
        atonementName: string,
    ): number {
        let matchedValue = 0;

        equippedAtonements.forEach((equipped: any) => {
            if (equipped.hasOwnProperty(atonementName)) {
                matchedValue = equipped[atonementName];
            }
        });

        return matchedValue;
    }

    renderAtonementChanges(originalAtonement: any, equippedAtonement: any) {
        if (typeof equippedAtonement === "undefined") {
            return;
        }

        return (
            <BasicCard>
                <h4 className="my-4">
                    {equippedAtonement.item_name} Atonement Adjustment
                </h4>
                <dl>
                    {this.atonementChanges(
                        originalAtonement,
                        equippedAtonement.data.atonements,
                    )}
                </dl>
            </BasicCard>
        );
    }

    render() {
        return (
            <div
                className={clsx({
                    "grid lg:grid-cols-2 gap-2 max-h-[150px] lg:max-h-full overflow-y-scroll lg:overflow-y-visible":
                        typeof this.props.equipped_atonements[0] !==
                        "undefined",
                })}
            >
                <div>
                    <BasicCard>
                        <RenderAtonementDetails
                            title={"This Items Atonement"}
                            original_atonement={this.props.item_atonement}
                        />
                        <h4 className="my-4">Elemental Atonement</h4>
                        <dl>
                            <dt>Primary Element</dt>
                            <dd>
                                {
                                    this.props.item_atonement.elemental_damage
                                        .name
                                }
                            </dd>
                            <dt>Elemental Damage</dt>
                            <dd>
                                {(
                                    this.props.item_atonement.elemental_damage
                                        .amount * 100
                                ).toFixed(0)}
                                %
                            </dd>
                        </dl>
                    </BasicCard>
                </div>
                {typeof this.props.equipped_atonements[0] !== "undefined" ? (
                    <div>
                        {this.renderAtonementChanges(
                            this.props.item_atonement,
                            this.props.equipped_atonements[0],
                        )}

                        {typeof this.props.equipped_atonements[1] !==
                        "undefined" ? (
                            <Fragment>
                                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6"></div>
                                {this.renderAtonementChanges(
                                    this.props.item_atonement,
                                    this.props.equipped_atonements[1],
                                )}
                            </Fragment>
                        ) : null}
                    </div>
                ) : null}
            </div>
        );
    }
}
