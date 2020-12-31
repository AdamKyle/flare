import React from 'react';
import ContentLoader from 'react-content-loader';
import CardTemplate from '../components/templates/card-template';
import KingdomModal from './modal/kingdom-modal';

export default class Management extends React.Component {

    constructor(props) {
        super(props);

        this.state = {
            xPosition: props.xPosition,
            yPosition: props.yPosition,
            openSettleModal: false,
            is_own_kingdom: false,
            can_settle: true,
            isLoading: true,
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
            const data = result.data;

            this.setState({
                is_own_kingdom: this.isOwnKingdom(data),
                can_settle: this.canSettle(data),
                isLoading: false,
            })
        }).catch((error) => {
            console.error(error);
        });
    }

    isOwnKingdom(data) {
        if (this.props.isPort !== null) {
            return false;
        }

        if (_.isEmpty(data)) {
            return false;
        }
        
        return data.character_id === this.props.characterId;
    }

    canSettle(data) {
        if (this.props.isPort !== null) {
            return false;
        }
        
        if (_.isEmpty(data)) {
            return true
        }

        return this.isOwnKingdom(data);
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

                        <div className={this.state.is_own_kingdom ? "col-md-10 text-align-left hide" : "col-md-10 text-align-left"}>
                            <button className="btn btn-primary" onClick={this.openKingdomModal.bind(this)}>Settle Kingdom</button>
                        </div>

                        <div className={!this.state.is_own_kingdom ? "col-md-10 text-align-left hide" : "col-md-10 text-align-left"}>
                            <button className="btn btn-primary" onClick={this.openKingdomModal.bind(this)}>Manage Kingdom</button>
                        </div>
                    </div>
                </div>

                {this.state.openSettleModal ? <KingdomModal characterId={this.props.characterId} show={this.state.openSettleModal} x={this.state.xPosition} y={this.state.yPosition} close={this.closeKingdomModal.bind(this)} /> : null}
            </div>
        );
    }

    render() {
        
        if (this.state.isLoading) {
            return (
                <CardTemplate>
                    <ContentLoader viewBox="0 0 380 30">
                        {/* Only SVG shapes */}    
                        <rect x="0" y="0" rx="4" ry="4" width="250" height="5" />
                        <rect x="0" y="8" rx="3" ry="3" width="250" height="5" />
                        <rect x="0" y="16" rx="4" ry="4" width="250" height="5" />
                    </ContentLoader>
                </CardTemplate>
            );
        }

        return (
            <CardTemplate
                OtherCss="p-3"
                cardTitle="Kingdom Management"
                close={this.closeManagement.bind(this)}
            >
                
                {!this.state.can_settle && !this.state.is_own_kingdom ? <div className="alert alert-danger">You cannot settle here. Move along.</div> : this.kingdomDetails() }
            </CardTemplate>
        );
    }
}