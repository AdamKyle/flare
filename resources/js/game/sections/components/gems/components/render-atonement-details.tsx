import React, {Fragment} from "react";

export default class RenderAtonementDetails extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    renderAtonements(atonementData: any): JSX.Element[]|[] {
        const elements = [];

        for (const key in atonementData) {
            elements.push(
                <Fragment>
                    <dt>{key}</dt>
                    <dd>{(atonementData[key] * 100).toFixed(2)}%</dd>
                </Fragment>
            )
        }

        return elements;
    }


    render() {
        return (
            <Fragment>
                <h3 className='my-4'>{this.props.title}</h3>
                <dl>
                    {this.renderAtonements(this.props.original_atonement)}
                </dl>
            </Fragment>
        )
    }
}
