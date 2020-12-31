import React   from 'react';
import {Modal} from 'react-bootstrap';

export default class KingdomManagementModal extends React.Component {

    constructor(props) {
        super(props)

        this.state = {
        }
    }


    adjust(color, amount) {
        return '#' + color.replace(/^#/, '').replace(/../g, color => ('0'+Math.min(255, Math.max(0, parseInt(color, 16) + amount)).toString(16)).substr(-2));
    }

    getTreasury() {
        if (this.props.kingdom.treasury === null) {
            return 0;
        }

        return this.props.kingdom.treasury.toLocaleString('en-US', {maximumFractionDigits:0});
    }

    render() {
        if (this.state.kingdom === null) {
            return (<>One moment please ...</>);
        }

        console.log(this.props.kingdom.x_position);

        return (
            <Modal
                show={this.props.show}
                onHide={this.props.close}
                dialogClassName="large-modal"
                aria-labelledby="kingdom-management-modal"
                backdrop="static"
            >
                <Modal.Header closeButton style={{backgroundColor: this.adjust(this.props.kingdom.color, 50)}}>
                    <Modal.Title id="kingdom-management-modal" style={{color: '#fff'}}>
                        Manage Your Kingdom
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <div className="row">
                        <div className="col-md-4">
                            <dl>
                                <dt><strong>Name</strong>:</dt>
                                <dd>{this.props.kingdom.name}</dd>
                            </dl>
                        </div>
                        <div className="col-md-4">
                            <dl>
                                <dt><strong>Treasury</strong>:</dt>
                                <dd>{this.getTreasury()}</dd>
                            </dl>
                        </div>
                        <div className="col-md-4">
                            <dl>
                                <dt><strong>Location (X/Y)</strong>:</dt>
                                <dd>{this.props.kingdom.x_position} / {this.props.kingdom.y_position}</dd>
                            </dl>
                        </div>
                    </div>
                </Modal.Body>
            </Modal>
        );
    }
}