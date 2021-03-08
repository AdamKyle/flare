import React from 'react';

export default class KingdomSelection extends React.Component {

    constructor(props) {
        super(props);

        this.state = {
            kingdoms_selected: [],
            show_error: false,
        }
    }

    renderSelectOptions() {
        return this.props.kingdoms.map((kingdom) => {
            return (
                <option key={kingdom.id} value={kingdom.id}>
                    {kingdom.name} at {kingdom.x_position}/{kingdom.y_position}
                </option>
            )
        })
    }

    handleChange(event) {
        let value = Array.from(event.target.selectedOptions, option => parseInt(option.value));

        if (value.length > 10) {
            return this.setState({
                show_error: true,
            });
        }

        this.setState({
            show_error: false,
            kingdoms_selected: value,
        }, () => {
            if (value.length > 0) {
                this.props.setKingdoms(value);
                this.props.enableNext(true);
            } else {
                this.props.enableNext(false);
            }
        });
    }

    render() {
        return (
            <div className="container">
                {
                    this.state.show_error ?
                        <div className="alert alert-danger mt-2 mb-2">
                            You have selected too many kingdoms. Please adjust your selection.
                        </div>
                    : null
                }
                <div className="alert alert-info mb-2 mt-2">
                    You may only select 10 kingdoms at a time to attack from.
                </div>
                <div className="form-group mt-2">
                    <label htmlFor="kingom-select">Select Kingdoms</label>
                    <select 
                        multiple={true} 
                        value={this.kingdoms_selected} 
                        onChange={this.handleChange.bind(this)}
                        className="form-control"
                        id="kingom-select"
                        style={{height: '220px'}}
                    >
                        {this.renderSelectOptions()}
                    </select>
                    <small id="kingom-select" className="form-text text-muted">
                        You can use CTRL/CMD and SHIFT for selections.
                    </small>
                </div>
            </div>
        );
    }
}