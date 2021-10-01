import React from 'react';
import {Row, Col} from 'react-bootstrap';
import TimeOutBar from '../timeout/timeout-bar';
import {getServerMessage} from '../helpers/server_message';
import moment from 'moment';

export default class AlchemyAction extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      canCraft: true,
      isDead: this.props.isDead,
      itemToCraft: 0,
      itemsToCraft: [],
      craftingType: null,
      showAlchemy: true,
      characterId: this.props.characterId,
      timeRemaining: this.props.timeRemaining,
      showSuccess: false,
      gold: 0,
      loading: true,
    }

    this.craftingTimeOut = Echo.private('show-crafting-timeout-bar-' + this.props.userId);
    this.topBar = Echo.private('update-top-bar-' + this.props.userId);
    this.alchemyList = Echo.private('update-alchemy-list-' + this.props.userId);

  }

  componentDidMount() {
    this.setState({
      showAlchemy: this.props.showAlchemy,
      characterId: this.props.characterId,
      isDead: this.props.isDead,
      goldDust: this.props.characterGoldDust,
      shards: this.props.characterShards,
    }, () => {
      axios.get('/api/alchemy/' + this.props.characterId).then((result) => {
        this.setState({
          itemsToCraft: result.data.items,
          itemToCraft: result.data.items[0].id,
          loading: false,
        });
      }).catch((err) => {
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
    });

    this.craftingTimeOut.listen('Game.Core.Events.ShowCraftingTimeOutEvent', (event) => {
      this.setState({
        canCraft: event.canCraft,
        timeRemaining: event.canCraft ? 0 : 10,
      }, () => {
        this.props.updateCanCraft(event.canCraft);
      });
    });

    this.topBar.listen('Game.Core.Events.UpdateTopBarBroadcastEvent', (event) => {
      this.setState({
        goldDust: event.characterSheet.goldDust,
        shards: event.characterSheet.shards,
      });
    });

    this.alchemyList.listen('Game.Skills.Events.UpdateCharacterAlchemyList', (event) => {
      this.setState({
        itemsToCraft: event.items,
      });
    });
  }

  componentDidUpdate(prevProps, prevState) {
    if (this.props.showAlchemy !== prevProps.showAlchemy) {
      this.setState({
        showAlchemy: this.props.showAlchemy,
      });
    }

    if (this.props.isDead !== prevProps.isDead) {
      this.setState({
        isDead: this.props.isDead,
      });
    }

    if (this.props.characterGoldDust !== prevProps.characterGoldDust) {
      this.setState({
        goldDust: this.props.characterGoldDust,
      });
    }

    if (this.props.characterShards !== prevProps.characterShards) {
      this.setState({
        shards: this.props.characterShards,
      });
    }

    if (prevState.itemsToCraft.length !== this.state.itemsToCraft.length && (this.state.itemsToCraft.length !== 0 && prevState.itemsToCraft.length !== 0)) {
      this.setState({
        showSuccess: true
      }, () => {
        setTimeout(() => {
          this.setState({
            showSuccess: false
          });
        }, 3000);
      })
    }
  }

  buildCraftableItemsOptions() {
    if (this.state.itemsToCraft !== null) {
      return this.state.itemsToCraft.map((item) => {
        const goldDust = item.gold_dust_cost.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        const shards   = item.shards_cost.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");

        return <option key={item.id} value={item.id}>{item.name} --> Cost to craft: {goldDust} Gold Dust, {shards} Shards</option>
      });
    }
  }

  setItemToCraft(event) {
    this.setState({
      itemToCraft: parseInt(event.target.value),
    });
  }

  renderCraftingDropDowns() {
    if (this.state.showAlchemy) {
      return (
        <select className="form-control mt-2" id="crafting" name="crafting"
                value={this.state.itemToCraft !== null ? this.state.itemToCraft : 1}
                onChange={this.setItemToCraft.bind(this)}
                disabled={this.state.isDead || !this.state.canCraft || this.props.isAdventuring}>
          {this.buildCraftableItemsOptions()}
        </select>
      );
    }
  }

  craft() {
    if (!this.state.canCraft) {
      return getServerMessage('cant_craft');
    }

    const foundItem = this.state.itemsToCraft.filter(item => item.id === this.state.itemToCraft)[0];

    this.setState({
      showSuccess: false,
    }, () => {
      axios.post('/api/transmute/' + this.props.characterId, {
        item_to_craft: this.state.itemToCraft,
      }).then((result) => {
        if (!_.isEmpty(result.data.items)) {
          this.setState({
            itemsToCraft: result.data.items,
            canCraft: false,
          }, () =>  {
            this.props.updateCanCraft(false);
          });
        }
      }).catch((err) => {
        if (err.hasOwnProperty('response')) {
          const response = err.response;

          if (response.status === 401) {
            return location.reload();
          }

          if (response.status === 429) {
            // Reload to show them their notification.
            return this.props.openTimeOutModal();
          }
        }
      });
    });
  }

  renderCraftingButton() {
    if (this.state.itemToCraft !== 0 && this.state.showAlchemy) {
      return (
        <button className="btn btn-primary mt-2"
                type="button"
                disabled={this.state.isDead || !this.state.canCraft || this.props.isAdventuring}
                onClick={this.craft.bind(this)}
        >
          Transmute!
        </button>
      );
    }

    return null;

  }

  render() {
    if (!this.state.showAlchemy) {
      return null;
    }

    if (this.state.loading) {
      return <p className="mt-2">One moment ...</p>
    }

    return (
      <>
        <div className={"row mb-2 mt-2 " + (!this.state.showSuccess ? 'hide' : '')}>
          <div className="col-md-10">
            <div className="alert alert-success">You got new items to craft! Check the list.</div>
          </div>
        </div>

        <Row>
          <Col xs={12} sm={12} md={8} lg={8} xl={6}>
            {this.renderCraftingDropDowns()}
          </Col>
          <Col xs={12} sm={12} md={4} lg={4} xl={6}>
            <Row>
              <Col xs={3}>
                {this.renderCraftingButton()}
              </Col>
              <Col xs={8}>
                {
                  this.state.itemToCraft !== 0 ?
                    <TimeOutBar
                      cssClass={'transmute-timeout'}
                      readyCssClass={'transmute-timeout'}
                      timeRemaining={this.state.timeRemaining}
                      channel={'show-crafting-timeout-bar-' + this.props.userId}
                      eventClass={'Game.Core.Events.ShowCraftingTimeOutEvent'}
                    />
                    : null
                }
              </Col>
            </Row>
          </Col>
        </Row>
      </>
    )
  }
}
