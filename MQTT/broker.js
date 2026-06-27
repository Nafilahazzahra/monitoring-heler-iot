const aedes = require('aedes')();
const net = require('net');

const port = Number(process.env.MQTT_PORT || 1883);
const host = process.env.MQTT_HOST || '0.0.0.0';

const server = net.createServer(aedes.handle);

server.listen(port, host, () => {
  console.log(`MQTT broker aktif di mqtt://${host}:${port}`);
});

aedes.on('client', (client) => {
  console.log(`Client terkoneksi: ${client ? client.id : 'unknown'}`);
});

aedes.on('clientDisconnect', (client) => {
  console.log(`Client terputus: ${client ? client.id : 'unknown'}`);
});

aedes.on('publish', (packet, client) => {
  if (!client) {
    return;
  }
  console.log(`Publish ${packet.topic}: ${packet.payload.toString()}`);
});
