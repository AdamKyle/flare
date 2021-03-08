import React            from 'react';
import {Modal, Button}  from 'react-bootstrap';
import KingdomSelection from './partials/attack-sections/kingdom-selection';
import UnitSelection    from './partials/attack-sections/unit-selection';

export default class KingdomAttackModal extends React.Component {

    constructor(props) {
        super(props);

        this.state = {
            currentStep: 0,
            steps: [
                'Select Kingdoms',
                'Send Units'
            ],
            kingoms: [],
            enableNext: false,
        }
    }

    componentDidMount() {
        console.log(this.props.kingdoms)
    }

    renderSteps() {
        return this.state.steps.map((step, index) => {
            const className = "circle " + (index === this.state.currentStep ? "active" : "");

            return (
                <li key={"step_" + index}>
                    <div>
                        <span className={className}>
                            {index + 1}
                        </span>
                        {step}
                    </div>
                </li>
            );
        });
    }

    enableNext(bool) {
        this.setState({
            enableNext: bool
        });
    }

    setKingdoms(value) {
        this.setState({
            kingdoms: value
        });
    }

    next() {
        if (this.state.currentStep === 0) {
            console.log('do api call for kingdoms selected', this.state.kingdoms);
        }

        this.setState({
            currentStep: this.state.currentStep + 1,
        });
    }

    render() {
        return (
            <Modal 
                show={this.props.show} 
                onHide={this.props.close}
                backdrop="static"
                size="lg"
                dialogClassName="large-modal"
            >
                <Modal.Header closeButton>
                    <Modal.Title>Attack Kingdom</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <div className="form-wizard">
                        <div className="steps clearfix">
                            <ul>
                                {this.renderSteps()}
                            </ul>
                        </div>
                    </div>
                    <div className="progress" style={{position: 'relative'}}>
                        <div className="progress-bar progress-bar-striped indeterminate">
                        </div>
                    </div>
                    {
                        this.state.currentStep === 0 ?
                            <KingdomSelection 
                                kingdoms={this.props.kingdoms} 
                                enableNext={this.enableNext.bind(this)}
                                setKingdoms={this.setKingdoms.bind(this)}
                            />
                        : null
                    }
                    {
                        this.state.currentStep === 1 ?
                            <UnitSelection />
                        : null
                    }
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={this.props.close}>
                        Close
                    </Button>
                    <Button variant="primary" onClick={this.next.bind(this)} disabled={!this.state.enableNext}>
                        Next
                    </Button>
                </Modal.Footer>
            </Modal>
        )
    }
}