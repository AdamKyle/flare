import React, {Fragment} from "react";
import clsx from "clsx";

export default class RenderAtonementAdjustment extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    renderDifference(atonementData: any, originalAtonement: any): JSX.Element[]|[] {

        const atonementKeys = Object.keys(atonementData);

        return atonementKeys.map((atonementName: string) => {

            const atonementValue = this.findElementAtonement(originalAtonement, atonementName);

            return (
                <Fragment>
                    <dt>{atonementName}</dt>
                    <dd
                        className={clsx({
                            'text-green-700 dark:text-green-500': atonementData[atonementName] > atonementValue,
                            'text-red-700 dark:text-red-500': atonementData[atonementName] < atonementValue
                        })}
                    >{(atonementData[atonementName] * 100).toFixed(0)}%</dd>
                </Fragment>
            );
        });
    }

    findElementAtonement(atonements: any, elementName: string): number {

        const atonementsKeys = Object.keys(atonements);

        const element = atonementsKeys.filter((atonementsName: string) => atonementsName === elementName);

        if (element.length > 0) {
            return atonements[element[0]];
        }

        return 0;
    }

    render() {
        return (
            <Fragment>
                <h3 className='my-4'>Adjusted Atonement</h3>
                <dl>
                    {this.renderDifference(this.props.atonement_for_comparison, this.props.original_atonement.atonements)}
                </dl>
            </Fragment>
        )
    }
}
