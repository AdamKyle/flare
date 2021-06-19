export const getServerMessage = (type) => {
  axios.get('/api/server-message', {
    params: {type: type}
  }).catch((error) => {
    if (error.hasOwnProperty('response')) {
      const response = error.response;

      if (response.status === 401) {
        return location.reload()
      }

      if (response.status === 429) {
        return this.props.openTimeOutModal()
      }
    }
  });
}
