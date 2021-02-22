import React from 'react';
import { Dropdown } from 'react-bootstrap';

export default class Card extends React.Component {

  constructor(props) {
    super(props);
  }

  changeType(event) {
    this.props.onChange(event.target.dataset.type);
  }

  buildDropDownOptions() {
    return this.props.buttons.map((button) => {
      return <Dropdown.Item onClick={this.changeType.bind(this)} data-type={button.type} key={"button-" + button.type}>{button.name}</Dropdown.Item>
    })
  }

  buildDropDown() {
    return (
      <>
      <div className="float-right">
        <Dropdown size="sm">
          <Dropdown.Toggle variant="primary" id="dropdown-basic">
            {this.props.buttonTitle}
          </Dropdown.Toggle>

          <Dropdown.Menu>
            {this.buildDropDownOptions()}
          </Dropdown.Menu>
        </Dropdown>
      </div>
      {
        this.props.hasOwnProperty('textBesideButton') ?
        <div className="float-right mr-2 mt-2">
          <strong>{this.props.textBesideButton}</strong>
        </div> : null
      }
      </>
    )
  }

  buildCustomButton() {
    if (this.props.customButtonType === 'drop-down' && this.props.showButton) {
      return this.buildDropDown();
    }
  }

  renderTitle() {
    return (
      <>
        <div className="clearfix">
          <h4 className="card-title float-left">{this.props.cardTitle}</h4>
          {
            this.props.hasOwnProperty('close') ?
              <button
                className="float-right btn btn-sm btn-danger"
                onClick={this.props.close}
              >
                Close
              </button>
            : null
          }
          { 
            this.props.hasOwnProperty('customButtonType') ? 
              this.buildCustomButton() 
            : null
          }
        </div>
        <hr />
      </>
    );
  }

  render() {
    return (
      <div className={"card " + (this.props.otherClasses ? this.props.otherClasses : '')}>
        <div className="card-body">
          {
            this.props.cardTitle ?
              this.renderTitle()
            : null
          }
          <div className="mb-2">
            {this.props.children}
          </div>
        </div>
      </div>
    )
  }
}