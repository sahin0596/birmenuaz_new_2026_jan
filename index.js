const http = require("http");
const fs = require("fs");
const path = require("path");

const rootDir = path.resolve(__dirname);

const mimeTypes = {
  ".html": "text/html",
  ".css": "text/css",
  ".js": "application/javascript",
  ".json": "application/json",
  ".jpg": "image/jpeg",
  ".jpeg": "image/jpeg",
  ".png": "image/png",
  ".svg": "image/svg+xml",
  ".ico": "image/x-icon",
  ".woff": "font/woff",
  ".woff2": "font/woff2",
  ".ttf": "font/ttf",
  ".eot": "application/vnd.ms-fontobject",
};

const server = http.createServer((req, res) => {
  let requestPath = decodeURIComponent(req.url.split("?")[0]);
  if (requestPath === "/") {
    requestPath = "/index.html";
  }

  const filePath = path.join(rootDir, requestPath);
  if (!filePath.startsWith(rootDir)) {
    res.writeHead(403, { "Content-Type": "text/plain" });
    return res.end("Forbidden");
  }

  fs.stat(filePath, (err, stats) => {
    if (err) {
      res.writeHead(404, { "Content-Type": "text/plain" });
      return res.end("Not Found");
    }

    if (stats.isDirectory()) {
      const indexFile = path.join(filePath, "index.html");
      return fs.stat(indexFile, (indexErr) => {
        if (indexErr) {
          res.writeHead(404, { "Content-Type": "text/plain" });
          return res.end("Not Found");
        }
        serveFile(indexFile, res);
      });
    }

    serveFile(filePath, res);
  });
});

function serveFile(filePath, res) {
  const ext = path.extname(filePath).toLowerCase();
  const contentType = mimeTypes[ext] || "application/octet-stream";

  fs.readFile(filePath, (err, content) => {
    if (err) {
      res.writeHead(500, { "Content-Type": "text/plain" });
      return res.end("Server Error");
    }
    res.writeHead(200, { "Content-Type": contentType });
    res.end(content);
  });
}

const port = process.env.PORT || 3000;
server.listen(port, () => {
  console.log(`Server running at http://localhost:${port}`);
});
