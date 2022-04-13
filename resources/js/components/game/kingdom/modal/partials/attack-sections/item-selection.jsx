import React from 'react';
import {Row, Col} from 'react-bootstrap';

export default class ItemSelection extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      slots_selected: [],
      show_error: false,
    }
  }

  componentDidMount() {
    this.props.enableNext(true);
  }

  renderSelectOptions() {
    return this.props.items.map((item) => {
      return (
        <option key={item.id} value={item.id}>
          {item.item.name} ({item.item.kingdom_damage * 100}%)
        </option>
      )
    })
  }

  handleChange(event) {
    let value = Array.from(event.target.selectedOptions, option => parseInt(option.value));

    this.setState({
      slots_selected: value,
    });
  }

  useItems() {
    axios.post('/api/use-items-on-kingdom/' + this.props.characterId, {
      slots_selected: this.state.slots_selected,
      defender_id: this.props.defenderId,
    })
    .then((result) => {
      this.props.updateItems(result.data.items);
    })
    .catch((err) => {
      if (err.hasOwnProperty('response')) {
        const response = err.response;

        if (response.status === 401) {
          return location.reload();
        }

        if (response.status === 429) {
          return this.props.openTimeOutModal();
        }
      }

      this.props.close();
    });
  }

  getTotalDamageToBeDone() {
    let totalDamage = 0;

    this.state.slots_selected.map((slot) => {
      const foundItem = this.props.items.filter((i) => i.id === slot);

      if (foundItem.length > 0) {
        totalDamage += foundItem[0].item.kingdom_damage;
      }
    });

    return totalDamage > 0 ? totalDamage : 0;
  }

  render() {
    return (
      <div className="container">
        <div className="alert alert-info">
          <p>
            You can skip this page if you don't want to use any items. You can select as many items as you would like.
            The damage will be added up at the bottom and done all at once. The defending player will be alerted.
          </p>
          <p>
            The damage shown below, will do damage to the kingdoms: Units <strong>and</strong> Buildings. If buildings
            are significantly damaged, the defending kingdoms morale will be updated.
          </p>
          <p>
            To use the items, select them and click "Use items". Clicking next will not use the items. You still need to send
            in a Settler, even if you do damage equal to or above 100%.
          </p>
        </div>
        <div className="form-group mt-2">
          <label htmlFor="kingdom-select">Select items to use</label>
          <select
            multiple={true}
            value={this.kingdoms_selected}
            onChange={this.handleChange.bind(this)}
            className="form-control"
            id="kingdom-select"
            style={{height: '220px'}}
          >
            {this.renderSelectOptions()}
          </select>
          <small id="kingdom-select" className="form-text text-muted">
            You can use CTRL/CMD and SHIFT for selections.
          </small>
        </div>
        <hr />
        <dl>
          <dt>Total Damage:</dt>
          <dd>{Math.round(this.getTotalDamageToBeDone() * 100)}%</dd>
        </dl>
        <hr />
        <button className="btn btn-primary mt-3" disabled={this.state.slots_selected.length > 0 ? false : true} onClick={this.useItems.bind(this)}>Use Items</button>
      </div>
    );
  }
}
