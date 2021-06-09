import React from 'react';
import Card from "../components/templates/card";

export default class TraverseSection extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      loading: true,
      maps: [],
      currentMap: null,
      selected_plane: 0,
      error_message: null,
    }
  }

  componentDidMount() {
    axios.get('/api/maps/' + this.props.characterId).then((result) => {
      this.setState({
        loading: false,
        maps: result.data.maps,
        currentMap: result.data.current_map,
      })
    }).catch((err) => {
      console.error(err);
    });
  }

  traverse() {
    if (this.state.selected_plane === 0) {
      this.setState({
        error_message: 'Selection cannot be blank. Please select a plane.'
      });
    } else {
      this.setState({
        error_message: null,
      }, () => {
        axios.post('/api/map/traverse/' + this.props.characterId, {
          map_id: this.state.selected_plane,
        }).then((result) => {
          this.hideTraverse();
        }).catch((err) => {
          if (err.hasOwnProperty('response')) {
            this.setState({
              error_message: err.response.data.message,
            });

            if (err.response.status === 429) {
              location.reload();
            }

            if (err.response.status === 401) {
              location.reload();
            }
          } else {
            console.err(err);
          }
        });
      });
    }
  }

  hideTraverse() {
    this.props.openTraverseSection(false)
  }

  handleSelection(e) {
    this.setState({
      selected_plane: e.target.value,
    });
  }

  fetchPlanes() {
    const mapSelections = [];

    this.state.maps.forEach((map) => {
      mapSelections.push(
        <option value={map.id} key={'plane-' + map.name}>{map.name}</option>
      )
    });

    return mapSelections;
  }

  render() {
    return (
      <Card
        OtherCss="p-3"
        cardTitle="Traverse"
        close={this.hideTraverse.bind(this)}
      >
        {
          this.state.loading ?
            <div className="progress loading-progress mt-2 mb-2" style={{position: 'relative'}}>
              <div className="progress-bar progress-bar-striped indeterminate">
              </div>
            </div>
            :
            <>
              {
                this.state.error_message !== null ?
                  <div className="alert alert-danger">{this.state.error_message}</div>
                  : null
              }
              <p><strong>Current Map</strong>: {this.state.currentMap}</p>

              <div className="form-row">
                <div className="form-group col-md-12">
                  <label htmlFor="x-position">Select Location</label>
                  <select className="form-control" id="x-position" onChange={this.handleSelection.bind(this)}
                          value={this.state.selected_plane}>
                    <option value={0} key={'plane'}>Please select</option>
                    {this.fetchPlanes()}
                  </select>
                </div>
                <div className="form-group col-md-12">
                  <button className="btn btn-primary" onClick={this.traverse.bind(this)}>Traverse</button>
                </div>
              </div>
            </>
        }
      </Card>
    );
  }
}
