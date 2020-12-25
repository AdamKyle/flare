import React from 'react';
import CardTemplate from '../components/templates/card-template';

export default class Management extends React.Component {

    constructor(props) {
        super(props);
    }

    closeManagement() {
        this.props.openKingdomManagement(false);
    }

    kingdomDetails() {
        return (
            <div className="row justify-content-center">
                <div className="col-md-12">
                    
                    <p>{this.props.isAdventuring !== null ? <em>You are adventuring. You are currently acting as a trusted adviser.</em> : ''}</p>
                    <p>Test Content</p>
                </div>
            </div>
        );
    }

    render() {
        return (
            <CardTemplate
                OtherCss="p-3"
                cardTitle="Kingdom Management"
                close={this.closeManagement.bind(this)}
            >
                {this.props.isPort !== null ? <div className="alert alert-danger">You cannot settle here. This is a port city. Move along.</div> : this.kingdomDetails() }
            </CardTemplate>
        );
    }
}