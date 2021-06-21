import React from 'react';

export default class ItemName extends React.Component {

  constructor(props) {
    super(props);
  }

  getClass() {
    if (this.props.item.item_prefix_id !== null && this.props.item.item_suffix_id !== null) {
      return 'two-enchant';
    }

    if (this.props.item.item_prefix_id !== null || this.props.item.item_suffix_id !== null) {
      return 'one-enchant';
    }

    return 'normal-item';
  }

  render() {
    return (
      <span className={this.getClass()}>{this.props.item.name}</span>
    );
  }
}
