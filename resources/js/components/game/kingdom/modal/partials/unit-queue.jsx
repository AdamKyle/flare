import React     from 'react';
import {Modal, ModalDialog}   from 'react-bootstrap';
import Draggable from 'react-draggable';
import UnitData from './unit-data';

class DraggableModalDialog extends React.Component {
    render() {
        return (
            <Draggable handle=".modal-title">
                <div>
                    <ModalDialog {...this.props} />
                </div> 
            </Draggable>
        );
    }
}

export default class UnitQueue extends React.Component {

    constructor(props) {
        super(props)

        this.state = {
            unit: null,
            queue: null,
            error: null,
        }
    }

    componentDidMount() {
        this.setState({
            unit: this.fetchUnit(),
            queue: this.props.queueData,
        });
    }

    fetchUnit() {
        const unit = this.props.units.filter((u) => u.id === this.props.queueData.game_unit_id);
        
        if (_.isEmpty(unit)) {
            return null;
        }

        return unit[0];
    }

    cancelUpgrade() {
        axios.post('/api/kingdoms/recruit-units/cancel', {
            queue_id: this.state.queue.id
        }).then((result) => {
            this.props.close();
        }).catch((err) => {
            this.setState({
                error: err.response.data.message,
            });
        });
    }

    getIncrease(type) {
        const building = this.state.unit;
        
        if (building.hasOwnProperty('future_' + type + '_increase')) {
            return building['future_' + type + '_increase'];
        }

        return 0;
    }

    upgradeDetails() {
        return <UnitData kingdom={this.props.kingdom} unit={this.state.unit} amount={this.state.queue.amount} />
    }

    modalContent() {
        return(
            <>
                <p>{this.state.unit.description}</p>
                <hr />
                <h5 className="mb-2">For Amount: {this.state.queue.amount}</h5>
                {this.upgradeDetails()}
                <hr />
                <div className="alert alert-warning">
                    If you cancel this upgrade, you'll get a perentage of the materials and population back based on
                    the amount of time left. If the resources you would get back are less then 10%, you wont be able to
                    cancel the unit recruitment.
                </div>
            </>
        );
    }

    render() {
        return (
            <Modal
                dialogAs={DraggableModalDialog}
                show={this.props.show}
                onHide={this.props.close}
                aria-labelledby="building-queue-modal"
                dialogClassName="building-queue-management"
                centered
            >
                <Modal.Header closeButton>
                    <Modal.Title id="building-queue-management-modal">
                        {this.state.unit !== null ? this.state.unit.name : 'One second ...'}
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    {
                        this.state.error !== null ? <div className="alert alert-danger">{this.state.error}</div> : null 
                    }
                    {
                        this.state.unit === null ? 'One second' : this.modalContent()
                    }
                </Modal.Body>
                <Modal.Footer>
                    <button className="btn btn-danger" onClick={this.props.close}>close</button>
                    <button className="btn btn-success" onClick={this.cancelUpgrade.bind(this)}>Cacnel Upgrade</button>
                </Modal.Footer>
            </Modal>
        );
    }
}