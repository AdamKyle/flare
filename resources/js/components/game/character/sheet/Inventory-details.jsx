import React from 'react';
import {Card} from 'react-bootstrap';
import InventoryBase from "../inventory/inventory-base";

export default class InventoryDetails extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      loading: true,
      inventoryBaseInfo: {},
    }

    this.updateInventoryDetails = Echo.private('update-inventory-details-' + this.props.userId);
  }

  componentDidMount() {
    axios.get('/api/character-sheet/'+this.props.characterId+'/base-inventory-info').then((result) => {
      this.setState({
        loading: false,
        inventoryBaseInfo: result.data.inventory_info
      });
    }).catch((err) => {
      this.setState({loading: false});
      if (err.hasOwnProperty('response')) {
        const response = err.response;

        if (response.status === 401) {
          return location.reload()
        }

        if (response.status === 429) {
          return this.props.openTimeOutModal()
        }
      }
    });

    this.updateInventoryDetails.listen('Game.Core.Events.CharacterInventoryDetailsUpdate', (event) => {
      this.setState({
        inventoryBaseInfo: event.inventoryDetails,
      });
    });
  }

  render() {
    if (this.state.loading) {
      return (
        <div className="progress loading-progress mt-2 mb-2" style={{position: 'relative'}}>
          <div className="progress-bar progress-bar-striped indeterminate">
          </div>
        </div>
      );
    }

    return (
      <>
        <Card>
          <Card.Body>
            <dl>
              <dt>Total gold:</dt>
              <dd>{this.state.inventoryBaseInfo.gold}</dd>
              <dt>Total gold dust:</dt>
              <dd>{this.state.inventoryBaseInfo.gold_dust}</dd>
              <dt>Total shards:</dt>
              <dd>{this.state.inventoryBaseInfo.shards}</dd>
              <dt>Used / Max inventory space:</dt>
              <dd>{this.state.inventoryBaseInfo.inventory_used} / {this.state.inventoryBaseInfo.inventory_max}</dd>
              <dt>Stat to focus on for max damage:</dt>
              <dd>{this.state.inventoryBaseInfo.damage_stat}</dd>
              <dt>To focus on for Hit%:</dt>
              <dd>Accuracy (skill) and {this.state.inventoryBaseInfo.to_hit_stat}</dd>
            </dl>
          </Card.Body>
        </Card>
        <InventoryBase characterId={this.props.characterId} userId={this.props.userId} automations={this.props.automations}/>
      </>
    );
  }
}