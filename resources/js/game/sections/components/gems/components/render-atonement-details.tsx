import React, {Fragment} from "react";

export default class RenderAtonementDetails extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    renderAtonements(atonementData: any): JSX.Element[]|[] {
        return atonementData.map((data: any) => {
            return (
                <Fragment>
                    <dt>{data.name}</dt>
                    <dd>{(data.total * 100).toFixed(2)}%</dd>
                </Fragment>
            )
        })
    }


    render() {
        return (
            <Fragment>
                <h3 className='my-4'>{this.props.title}</h3>
                <dl>
                    {this.renderAtonements(this.props.original_atonement.atonements)}
                </dl>
            </Fragment>
        )
    }
}
