const { SerialPort } = require('serialport');
const { ReadlineParser } = require('@serialport/parser-readline');
const mqtt = require('mqtt');

const serialPortName = process.env.SERIAL_PORT || 'COM3';
const serialBaudRate = Number(process.env.SERIAL_BAUD || 115200);
const brokerUrl = process.env.MQTT_URL || 'mqtt://127.0.0.1:1883';
const topic = process.env.MQTT_TOPIC || 'heler/sensor';
const tankHeightCm = Number(process.env.TANK_HEIGHT_CM || 100);

const mqttClient = mqtt.connect(brokerUrl, { clientId: `heler-serial-bridge-${Date.now()}` });

const port = new SerialPort({
  path: serialPortName,
  baudRate: serialBaudRate,
});

const parser = port.pipe(new ReadlineParser({ delimiter: '\r\n' }));

let frame = {};

function computeStatus(data) {
  if (data.vibration_status === 'Terlalu Bergetar (Tidak Normal)') {
    return 'Tidak Normal';
  }

  if (data.vibration_status === 'Normal') {
    return 'Normal';
  }

  return 'Mesin Mati';
}

function flushFrame() {
  if (
    typeof frame.temperature !== 'number' ||
    typeof frame.flow_rate !== 'number' ||
    typeof frame.water_level !== 'number' ||
    typeof frame.vibration_status !== 'string'
  ) {
    frame = {};
    return;
  }

  const payload = {
    ...frame,
    status: computeStatus(frame),
    recorded_at: new Date().toISOString(),
  };

  mqttClient.publish(topic, JSON.stringify(payload), { qos: 0 }, (error) => {
    if (error) {
      console.error('Gagal publish ke MQTT:', error.message);
      return;
    }
    console.log('Data dipublish ke MQTT:', payload);
  });

  frame = {};
}

parser.on('data', (line) => {
  const text = line.trim();

  if (!text) {
    return;
  }

  if (text.includes('=== REAL-TIME SENSOR DATA')) {
    frame = {};
    return;
  }

  if (text.startsWith('Vibration Count')) {
    const raw = Number(text.split(':')[1]?.trim() || 0);
    frame.vibration_count = raw;
    return;
  }

  if (text.startsWith('Vibration Status')) {
    frame.vibration_status = text.split(':')[1]?.trim() || 'Tidak Bergetar';
    return;
  }

  if (text.startsWith('Vibration Value')) {
    const raw = Number(text.split(':')[1]?.trim() || 0);
    frame.vibration_value = raw;
    return;
  }

  if (text.startsWith('Water Distance')) {
    const match = text.match(/Water Distance\s*:\s*([\d.]+)/i);
    if (match) {
      const distance = Number(match[1]);
      const level = Math.max(0, Math.min(100, ((tankHeightCm - distance) / tankHeightCm) * 100));
      frame.distance_cm = distance;
      frame.water_level = Number(level.toFixed(2));
    }
    return;
  }

  if (text.startsWith('Water Flow Rate')) {
    const match = text.match(/Water Flow Rate\s*:\s*([\d.]+)/i);
    frame.flow_rate = match ? Number(match[1]) : 0;
    return;
  }

  if (text.startsWith('Temperature')) {
    const match = text.match(/Temperature\s*:\s*(-?[\d.]+)/i);
    if (match) {
      frame.temperature = Number(match[1]);
      flushFrame();
    }
  }
});

port.on('open', () => {
  console.log(`Serial bridge aktif di ${serialPortName} (${serialBaudRate} baud)`);
});

port.on('error', (error) => {
  console.error('Serial error:', error.message);
});

mqttClient.on('connect', () => {
  console.log(`Terhubung ke broker ${brokerUrl} dan publish ke topic ${topic}`);
});

mqttClient.on('error', (error) => {
  console.error('MQTT error:', error.message);
});
