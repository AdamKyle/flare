import React from 'react';
import TimeOutBar from '../timeout/timeout-bar';
import { getServerMessage } from '../helpers/server_message';

export default class CraftingAction extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      canCraft: true,
      isDead: this.props.isDead,
      itemToCraft: 0,
      itemsToCraft: [],
      craftingType: null,
      showCrafting: true,
      chracterId: this.props.characterId,
      timeRemaining: this.props.timeRemaining,
      showSuccess: false,
      gold: 0,
    }

    this.craftingTimeOut = Echo.private('show-crafting-timeout-bar-' + this.props.userId);
  }

  componentDidMount() {
    this.setState({
      showCrafting: this.props.showCrafting,
      characterId: this.props.characterId,
      isDead: this.props.isDead,
      gold: this.props.characterGold,
    });

    this.craftingTimeOut.listen('Game.Core.Events.ShowCraftingTimeOutEvent', (event) => {
      this.setState({
        canCraft:      event.canCraft,
        timeRemaining: event.canCraft ? 0 : 10,
      }, () => {
        this.props.updateCanCraft(event.canCraft);
      });
    });
  }

  componentDidUpdate(prevProps, prevState) {
    if (this.props.showCrafting !== prevProps.showCrafting) {
      this.setState({
        showCrafting: this.props.showCrafting,
      });
    }

    if (this.props.isDead !== prevProps.isDead) {
      this.setState({
        isDead: this.props.isDead,
      });
    }

    if (this.props.shouldChangeCraftingType) {
      this.setState({
        craftingType: null,
        itemsToCraft: [],
        itemToCraft: 0,
      }, () => {
        this.props.changeCraftingType(false);
      });
    }

    if (this.props.characterGold !== prevProps.characterGold) {
      this.setState({
        gold: this.props.characterGold,
      });
    }

    if (prevState.itemsToCraft.length !== this.state.itemsToCraft.length && prevState.itemsToCraft.length !== 0) {
      this.setState({
        showSuccess: true
      }, () => {
        setTimeout( () => {
          this.setState({
            showSuccess: false
          });
        }, 3000);
      })
    }
  }

  updateCraftingType(event) {
    this.setState({
      craftingType: event.target.value,
    }, () => {
      if (this.state.craftingType !== null) {
        axios.get('/api/crafting/' + this.state.characterId, {
          params: {
            crafting_type: this.state.craftingType
          }
        }).then((result) => {
          this.setState({
            itemsToCraft: result.data.items,
            itemToCraft: result.data.items[0].id,
          });
        });
      }
    });
  }

  buildCraftableItemsOptions() {
    if (this.state.itemsToCraft !== null) {
      return this.state.itemsToCraft.map((item) => {
        return <option key={item.id} value={item.id}>{item.name} --> Cost to craft: {item.cost} Gold</option>
      });
    }
  }

  setItemToCraft(event) {
    this.setState({
      itemToCraft: parseInt(event.target.value),
    });
  }

  craft() {
    if (!this.state.canCraft) {
      return getServerMessage('cant_craft');
    }

    const foundItem = this.state.itemsToCraft.filter(item => item.id === this.state.itemToCraft)[0];

    if (foundItem.cost > this.state.gold) {
      return getServerMessage('not_enough_gold');
    }

    this.setState({
      canCraft: false,
      showSuccess: false,
    }, () => {
      this.props.updateCanCraft(event.canCraft);
      
      axios.post('/api/craft/' + this.state.characterId, {
        item_to_craft: this.state.itemToCraft,
        type: this.state.craftingType,
      }).then((result) => {
        if (!_.isEmpty(result.data.items)) {
          this.setState({
            itemsToCraft: result.data.items
          });
        }
      });
    });
  }

  renderCraftingDropDowns() {
    if (this.state.showCrafting) {
      if (this.state.craftingType === null) {
        return (
          <select className="form-control ml-3 mt-2" id="crafting-type" name="crafting-type"
            value={0}
            onChange={this.updateCraftingType.bind(this)}
            disabled={this.state.isDead}>
            <option value="" key="0">Please select a crafting type</option>
            <option value="Weapon" key="weapon">Weapon</option>
            <option value="Armour" key="armour">Armour</option>
            <option value="Spell" key="spell">Spell</option>
            <option value="Ring" key="ring">Ring</option>
            <option value="Artifact" key="artifact">Artifact</option>
          </select>
        );
      }

      return (
        <select className="form-control ml-3 mt-2" id="crafting" name="crafting"
          value={this.state.itemToCraft !== null ? this.state.itemToCraft : 1}
          onChange={this.setItemToCraft.bind(this)}
          disabled={this.state.isDead || !this.state.canCraft}>
          {this.buildCraftableItemsOptions()}
        </select>
      );
    }
  }

  renderCraftingButton() {
    if (this.state.itemToCraft !== 0 && this.state.showCrafting) {
      return (
        <button className="btn btn-primary mt-2"
          type="button"
          disabled={this.state.isDead || !this.state.canCraft}
          onClick={this.craft.bind(this)}
        >
          Craft!
        </button>
      );
    }

    return null;

  }

  render() {
    if (!this.state.showCrafting) {
      return null;
    }

    return (
      <>
        <div className={"container justify-content-center " + (!this.state.showSuccess ? 'hide' : '')}>
          <div className="row">
            <div className="col-md-10">
              <div className="alert alert-success">You got new items to craft! Check the list.</div>
            </div>
          </div>
          
        </div>
        
        <div className="form-group row">
          <div className="col-md-8">
            {this.renderCraftingDropDowns()}
            
          </div>
          <div className="col-md-1">
            {this.renderCraftingButton()}
          </div>
          <div className="col-md-3">
            <div className="ml-4 mt-3">
              {this.state.itemToCraft !== 0 ?
                <TimeOutBar 
                  cssClass={'character-timeout'}
                  readyCssClass={'character-ready'}
                  timeRemaining={this.state.timeRemaining}
                  channel={'show-crafting-timeout-bar-' + this.props.userId}
                  eventClass={'Game.Core.Events.ShowCraftingTimeOutEvent'}
                />
                : null
              }
            </div>
          </div>
        </div>
      </>
    )
  }
} 