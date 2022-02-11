import React from 'react';

export default class EquippedSectionButton extends React.Component {

  constructor(props) {
    super(props);

  }

  unequipItem() {
    this.props.setLoading(true);
    axios.post('/api/character/'+this.props.characterId+'/inventory/unequip', {
      inventory_set_equipped: this.props.hasSetEquipped,
      item_to_remove: this.props.findEquippedSlotId(this.props.item.id)
    }).then((result) => {
      this.props.setLoading(false);
      this.props.setSuccessMessage(result.data.message)
    }).catch((error) => {
      this.props.setLoading(false);
      if (error.hasOwnProperty('response')) {
        const response = error.response;

        if (response.status === 401) {
          return location.reload()
        }

        if (response.status === 429) {
          return window.location.replace('/game');
        }

        if (response.data.hasOwnProperty('message')) {
          this.props.setErrorMessage(response.data.message)
        }

        if (response.data.hasOwnProperty('error')) {
          this.props.setErrorMessage(response.data.error)
        }
      }
    })
  }

  render() {
    return (
      <button onClick={this.unequipItem.bind(this)} className="btn btn-primary">
        Unequip
      </button>
    );
  }
}
