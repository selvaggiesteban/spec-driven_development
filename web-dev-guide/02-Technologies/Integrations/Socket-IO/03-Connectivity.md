##### 5.9.8.3 Connectivity

- Socket.IO establishes a persistent bidirectional connection between the client and server.
- The connection begins with an HTTP handshake, then attempts to upgrade to WebSocket if possible, or uses other transports.
- Handle automatic client reconnections in case of connection loss.
- Ensure that necessary ports (e.g., 80/443 for HTTP/HTTPS) are open and configured on the server/firewall.
- Configure reverse proxies and load balancers to support the persistent nature of WebSocket connections.
