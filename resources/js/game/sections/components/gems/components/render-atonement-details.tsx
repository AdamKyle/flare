import React, { Fragment, ReactNode } from "react";

export default class RenderAtonementDetails extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    renderAtonements(atonementData: any): ReactNode[] | [] {
        const atonements = atonementData.atonements;

        const atonementNames = Object.keys(atonements);

        return atonementNames.map((name) => (
            <Fragment key={name}>
                <dt>{name}</dt>
                <dd>{(atonements[name] * 100).toFixed(0)}%</dd>
            </Fragment>
        ));
    }

    render() {
        return (
            <Fragment>
                <h3 className="my-4">{this.props.title}</h3>
                <dl>{this.renderAtonements(this.props.original_atonement)}</dl>
            </Fragment>
        );
    }
}
