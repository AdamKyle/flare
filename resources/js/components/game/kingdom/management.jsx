import React from 'react';
import CardTemplate from '../components/templates/card-template';
import KingdomModal from './modal/kingdom-modal';

export default class Management extends React.Component {

    constructor(props) {
        super(props);

        this.state = {
            xPosition: props.xPosition,
            yPosition: props.yPosition,
            openSettleModal: false,
        }
    }

    componentDidMount() {
        this.fetchKingdomDataAtLocation();
    }

    componentDidUpdate() {
        if (this.state.xPosition !== this.props.xPosition || this.state.yPosition !== this.props.yPosition) {
            this.setState({
                xPosition: this.props.xPosition,
                yPosition: this.props.yPosition,
            }, () => {
                this.fetchKingdomDataAtLocation();
            });
        }

        if (this.state.xPosition !== this.props.xPosition && this.state.yPosition !== this.props.yPosition) {
            this.setState({
                xPosition: this.props.xPosition,
                yPosition: this.props.yPosition,
            }, () => {
                this.fetchKingdomDataAtLocation();
            });
        }
    }

    fetchKingdomDataAtLocation() {
        axios.get('/api/kingdoms/location', {
            params: {
                x_position: this.state.xPosition,
                y_position: this.state.yPosition,
            }
        }).then((result) => {
            console.log(result);
        }).catch((error) => {
            console.error(error);
        });
    }

    closeManagement() {
        this.props.openKingdomManagement(false);
    }

    openKingdomModal() {
        this.setState({
            openSettleModal: true
        });
    }

    closeKingdomModal() {
        this.setState({
            openSettleModal: false,
        });
    }

    kingdomDetails() {
        return (
            <div className="row justify-content-center">
                <div className="col-md-12">
                    
                    <p>{this.props.isAdventuring !== null ? <em>You are adventuring. You are currently acting as a trusted adviser.</em> : ''}</p>
                    
                    <div className="row">
                        <div className="col-md-2 mt-2">
                        <strong>Location (X/Y):</strong> {this.state.xPosition} / {this.state.yPosition}
                        </div>
                        <div className="col-md-10 text-align-left">
                            <button className="btn btn-primary" onClick={this.openKingdomModal.bind(this)}>Settle Kingdom</button>
                        </div>
                    </div>
                </div>

                {this.state.openSettleModal ? <KingdomModal characterId={this.props.characterId} show={this.state.openSettleModal} x={this.state.xPosition} y={this.state.yPosition} close={this.closeKingdomModal.bind(this)} /> : null}
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