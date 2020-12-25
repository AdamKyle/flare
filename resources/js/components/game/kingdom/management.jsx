import React from 'react';
import CardTemplate from '../components/templates/card-template';

export default class Management extends React.Component {

    constructor(props) {
        super(props);
    }

    closeManagement() {
        this.props.openKingdomManagement(false);
    }

    render() {
        return (
            <CardTemplate
                OtherCss="p-3"
                cardTitle="Kingdom Management"
                close={this.closeManagement.bind(this)}
            >
                <div className="row justify-content-center">
                    <div className="col-md-12">
                        Test Content
                    </div>
                </div>
            </CardTemplate>
        );
    }
}