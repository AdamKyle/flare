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

    if (this.props.item.usable) {
      return 'usable-item';
    }

    return 'normal-item';
  }

  render() {
    return (
      <span className={this.getClass()}>{this.props.useAffixName ? this.props.item.affix_name : this.props.item.name}</span>
    );
  }
}
