const mqtt = require('mqtt');

const brokerUrl = process.env.MQTT_URL || 'mqtt://127.0.0.1:1883';
const topic = process.env.MQTT_TOPIC || 'heler/sensor';

const payload = {
  temperature: 72.5,
  flow_rate: 4.12,
  water_level: 43.8,
  vibration_status: 'Normal',
  status: 'Optimal',
  recorded_at: new Date().toISOString(),
};

const client = mqtt.connect(brokerUrl, { clientId: 'heler-sample-publisher' });

client.on('connect', () => {
  client.publish(topic, JSON.stringify(payload), { qos: 0 }, (error) => {
    if (error) {
      console.error('Gagal publish sample:', error.message);
    } else {
      console.log('Sample data berhasil dipublish:', payload);
    }
    client.end();
  });
});

client.on('error', (error) => {
  console.error('Koneksi MQTT gagal:', error.message);
  client.end();
});
