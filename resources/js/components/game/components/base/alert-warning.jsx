import React from 'react';

export default class AlertWarning extends React.Component {

  constructor(props) {
    super(props);
  }

  render() {
    return (
      <div className="tw-px-4 tw-py-3 tw-leading-normal tw-bg-yellow-100 tw-rounded-md tw-drop-shadow-sm tw-mb-3">
        <p className="font-bold tw-mb-2 tw-text-yellow-700"><i className={this.props.icon}></i> {this.props.title}</p>
        {this.props.children}
      </div>
    )
  }
}