import React from 'react';

export default class AlertSuccess extends React.Component {

  constructor(props) {
    super(props);
  }

  render() {
    return (
      <div className="tw-px-4 tw-py-3 tw-leading-normal tw-bg-green-100 tw-rounded-md tw-drop-shadow-sm tw-mb-3">
        <p className="tw-font-bold tw-mb-2 tw-text-green-700"><i className={this.props.icon}></i> {this.props.title}</p>
        {this.props.children}
        {
          this.props.showClose ?
            <span className="tw-absolute tw-top-0 tw-bottom-0 tw-right-0 tw-px-4 tw-py-3 tw-text-green-700 tw-font-bold tw-cursor-pointer"
                  onClick={this.props.closeAlert}>
              <i className="fas fa-times"></i>
            </span>
          : null
        }
      </div>
    )
  }
}