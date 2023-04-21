import React, {Fragment} from "react";
import clsx from "clsx";

export default class RenderAtonementAdjustment extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    renderDifference(atonementData: any, originalAtonement: any): JSX.Element[]|[] {
        return atonementData.map((data: any) => {

            const atonementValue = this.findElementAtonement(originalAtonement, data.name);

            return (
                <Fragment>
                    <dt>{data.name}</dt>
                    <dd
                        className={clsx({
                            'text-green-700 dark:text-green-500': data.total > atonementValue,
                            'text-red-700 dark:text-red-500': data.total < atonementValue
                        })}
                    >{(data.total * 100).toFixed(2)}%</dd>
                </Fragment>
            );
        });
    }

    findElementAtonement(originalAtonement: any, elementName: string): number {
        const element = originalAtonement.filter((atonement: any) => atonement.name === elementName);

        if (element.length > 0) {
            return element[0].total;
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
