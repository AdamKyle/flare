export const getServerMessage = (type) => {
  axios.get('/api/server-message', {
    params: {type: type}
  }).catch((error) => {
    console.log(error);
  });
}
