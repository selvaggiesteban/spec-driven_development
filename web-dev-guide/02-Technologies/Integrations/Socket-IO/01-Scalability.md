##### 5.9.8.1 Scalability

- Socket.IO enables real-time communication, but its scalability in distributed production environments requires consideration.
- To scale horizontally, it's necessary to configure a Socket.IO adapter compatible with Redis (or other distributed data store) so that events can be shared across multiple server instances.
- The use of load balancers that support sticky sessions (or equivalents) is fundamental to maintain a client's connection with the same server instance.
- Memory management per client and the volume of real-time messages are key factors for server scalability.
