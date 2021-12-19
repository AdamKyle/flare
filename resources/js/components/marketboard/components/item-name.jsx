import React from 'react';

export default class ItemName extends React.Component {

  constructor(props) {
    super(props);
  }

  getClass() {

    const isUnique = this.isUnique();

    if (isUnique) {
      return 'unique-item';
    }

    if (this.props.item.usable) {
      return 'usable-item';
    }

    if (this.props.item.type === 'quest') {
      return 'quest-item';
    }

    if (this.props.item.item_prefix_id !== null && this.props.item.item_suffix_id !== null) {
      return 'two-enchant';
    }

    if (this.props.item.item_prefix_id !== null || this.props.item.item_suffix_id !== null) {
      return 'one-enchant';
    }

    return 'normal-item';
  }

  isUnique() {
    if (this.props.item.item_prefix_id !== null) {
      return this.props.item.item_prefix.randomly_generated;
    }

    if (this.props.item.item_suffix_id !== null) {
      return this.props.item.item_suffix.randomly_generated;
    }

    return false;
  }

  render() {
    return (
      <span className={this.getClass()}>{this.props.useAffixName ? this.props.item.affix_name : this.props.item.name}</span>
    );
  }
}
