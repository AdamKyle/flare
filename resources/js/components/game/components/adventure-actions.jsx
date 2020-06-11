import React from 'react';
import AdventureEmbark from './modals/adventure-embark';

export default class AdeventureActions extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      adventureDetails: [],
      isLoading: true,
      showEmbark: false,
      adventure: null,
    }
  }

  componentDidMount() {
    this.setState({
      adventureDetails: this.props.adventureDetails,
      isLoading:   false,
    });
  }

  componentDidUpdate(prevProps) {
    if (this.props !== prevProps) {
      this.setState({
        adventureDetails: this.props.adventureDetails,
      });
    }
  }

  hideAdventure() {
    this.props.openAdventureDetails(false);
  }

  embarkShow(event) {
    const adveture = this.state.adventureDetails.filter(a => a.id === parseInt(event.target.getAttribute('data-adventure-id')))[0];

    this.setState({
      showEmbark: true,
      adventure: adveture,
    });
  }

  embarkClose() {
    this.setState({
      showEmbark: false,
      adventure: null,
    });
  }

  adventures() {
    const details = [];

    _.forEach(this.state.adventureDetails, (adventure) => {
        details.push(
            <div className="row mb-2" key={adventure.id}>
                <div className="col-md-2">{adventure.name}</div>
                <div className="col-md-10">
                    <button className="mr-2 btn btn-primary" data-adventure-id={adventure.id} onClick={this.embarkShow.bind(this)}>Embark</button>
                    <a href={'/adeventures/' + adventure.id} target="_blank" className="mr-2 btn btn-primary">Details</a>
                </div>
            </div>
        );
    });

    return details;
  }

  render() {
    if (this.state.isLoading) {
      return <>Please wait ...</>
    }

    return (
      <div className="card">
        <div className="card-body p-3">
          <div className="clearfix">
            <h4 className="card-title float-left">Adventures</h4>
            <button className="float-right btn btn-sm btn-danger" onClick={this.hideAdventure.bind(this)}>Close</button>
          </div>
          <hr />
          <div className="alert alert-info">You may only embark on one adventure at a time</div>
          {this.adventures()}
        </div>

        {this.state.showEmbark ? <AdventureEmbark adventure={this.state.adventure} show={this.state.showEmbark} embarkClose={this.embarkClose.bind(this)} /> : null }
      </div>
    )
  }
} 