const http = require("http");

const server = http.createServer((req, res) => {
  res.end("Render works!");
});

server.listen(3000, () => {
  console.log("Server running");
});
