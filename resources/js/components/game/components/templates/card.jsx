import React from 'react';

export default class Card extends React.Component {

  constructor(props) {
    super(props);
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