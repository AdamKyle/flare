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

    if (this.props.item.usable || this.props.item.can_use_on_other_items) {
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
    if (this.props.item.hasOwnProperty('unique')) {
      return this.props.item.unique;
    }

    if (this.props.item.item_prefix_id !== null) {
      if (this.props.item.item_prefix.randomly_generated) {
        return true;
      }
    }

    if (this.props.item.item_suffix_id !== null) {
      if (this.props.item.item_suffix.randomly_generated) {
        return true;
      }
    }

    return false;
  }

  render() {
    return (
      <span className={this.getClass()}>{this.props.useAffixName ? this.props.item.affix_name : this.props.item.name}</span>
    );
  }
}
