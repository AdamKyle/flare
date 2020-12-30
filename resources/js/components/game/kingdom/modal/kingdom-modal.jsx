import React from 'react';
import {Modal, Button} from 'react-bootstrap';
import ColorPicker from './partials/color-picker';

export default class KingdomModal extends React.Component {

    constructor(props) {
        super(props);

        this.state = {
            kingdom_name: '',
            color: null,
            errorMessage: null,
        }
    }

    updateErrorMessage(message) {
        this.setState({
            errorMessage: message,
        });
    }

    setColor(color) {
        this.setState({
            color: color,
        });
    }

    handleNameChange(event) {
        this.setState({
            kingdom_name: event.target.value
        });
    }

    settle() {
        const kingdomName = this.state.kingdom_name.trim();

        this.setState({
            errorMessage: null
        });

        if (kingdomName === '') {
            this.setState({
                errorMessage: 'Kingdom needs a name.'
            });
        }

        if (kingdomName.length < 5) {
            this.setState({
                errorMessage: 'Kingdom name must be minum of 5 characters.'
            })
        }

        if (kingdomName.length > 15) {
            this.setState({
                errorMessage: 'Kingdom name can only be maximum of 15 characters.'
            });
        }

        if (this.state.color === null) {
            this.setState({
                errorMessage: 'You need to pick a color.'
            });
        }

        const params = {
            name: this.state.kingdom_name,
            color: this.state.color,
            x_position: this.props.x,
            y_position: this.props.y,
        }

        axios.post('/api/kingdoms/'+this.props.characterId+'/settle', params).then((result) => {
            console.log(result);
        }).catch((error) => {
            console.error(error);
        });
    }

    render() {
        return(
            <Modal show={this.props.show} onHide={this.props.close} backdrop="static" keyboard={false}>
                <Modal.Header closeButton>
                <Modal.Title>Settle Kingdom</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <>
                        {this.state.errorMessage !== null ? <div className="alert alert-danger mt-2 mb-2">{this.state.errorMessage}</div> : null}
                        <form>
                            <div className="form-row">
                                <div className="form-group col-md-6">
                                    <label htmlFor="kingdom-name">Kingdom Name</label>
                                    <input type="text" className="form-control" id="kingdom-name" placeholder="Sample Name" value={this.kingdom_name} onChange={this.handleNameChange.bind(this)}/>
                                </div>
                                <div className="form-group col-md-6">
                                    <label>Kingdom Color</label>
                                    <ColorPicker updateErrorMessage={this.updateErrorMessage.bind(this)} setColor={this.setColor.bind(this)}/>
                                    <small className="form-text text-muted">Color transparency cannot be below 50%</small>
                                </div>
                            </div>
                        </form>
                    </>
                </Modal.Body>
                <Modal.Footer>
                <Button variant="danger" onClick={this.props.close}>
                    Cancel
                </Button>
                <Button variant="success" onClick={this.settle.bind(this)}>
                    Settle
                </Button>
                </Modal.Footer>
            </Modal>
        )
    }
}