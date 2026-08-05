/**
 * Local Telemetry Simulator - MicroClimeet
 * Generates and updates realistic mock sensor readings when Firebase is not connected.
 */

class TelemetrySimulator {
    constructor(thresholds, onUpdateCallback) {
        this.thresholds = thresholds || {
            temperature: { min: 20, max: 30 },
            humidity: { min: 60, max: 85 },
            air_quality: { min: 0, max: 50 },
            uv: { min: 0, max: 5 }
        };
        this.onUpdate = onUpdateCallback;
        this.intervalId = null;
        
        // Initial telemetry values
        this.currentData = {
            temperature: 24.5,
            humidity: 72.0,
            air_quality: 28.0,
            uv: 2.0
        };
    }

    start(intervalMs = 3000) {
        if (this.intervalId) return;
        
        console.log("Telemetry Simulator started.");
        
        // Initial trigger
        this.tick();
        
        this.intervalId = setInterval(() => {
            this.tick();
        }, intervalMs);
    }

    stop() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
            console.log("Telemetry Simulator stopped.");
        }
    }

    tick() {
        // Generate small variations to make it look realistic
        this.currentData.temperature += (Math.random() - 0.5) * 0.8;
        this.currentData.humidity += (Math.random() - 0.5) * 1.5;
        this.currentData.air_quality += (Math.random() - 0.5) * 3.0;
        this.currentData.uv += (Math.random() - 0.5) * 0.5;

        // Keep values in realistic boundaries
        this.currentData.temperature = Math.max(15, Math.min(40, this.currentData.temperature));
        this.currentData.humidity = Math.max(30, Math.min(100, this.currentData.humidity));
        this.currentData.air_quality = Math.max(5, Math.min(150, this.currentData.air_quality));
        this.currentData.uv = Math.max(0, Math.min(12, this.currentData.uv));

        // Format decimal places
        const formattedData = {
            temperature: parseFloat(this.currentData.temperature.toFixed(1)),
            humidity: parseFloat(this.currentData.humidity.toFixed(1)),
            air_quality: parseFloat(this.currentData.air_quality.toFixed(1)),
            uv: parseFloat(this.currentData.uv.toFixed(1))
        };

        // Determine status for each sensor based on thresholds
        const statuses = {};
        for (const [key, value] of Object.entries(formattedData)) {
            statuses[key] = this.evaluateStatus(key, value);
        }

        if (this.onUpdate) {
            this.onUpdate(formattedData, statuses);
        }
    }

    evaluateStatus(sensorType, value) {
        const threshold = this.thresholds[sensorType];
        if (!threshold) return { text: 'OPTIMAL', class: 'badge-success', level: 'optimal' };

        const val = parseFloat(value);
        const min = parseFloat(threshold.min);
        const max = parseFloat(threshold.max);

        if (val < min) {
            // Under threshold alert
            const diff = min - val;
            if (diff > min * 0.2) {
                return { text: 'KRITIS', class: 'badge-danger', level: 'danger' };
            }
            return { text: 'PERINGATAN', class: 'badge-warning', level: 'warning' };
        } else if (val > max) {
            // Over threshold alert
            const diff = val - max;
            if (diff > max * 0.2) {
                return { text: 'KRITIS', class: 'badge-danger', level: 'danger' };
            }
            return { text: 'PERINGATAN', class: 'badge-warning', level: 'warning' };
        }

        return { text: 'OPTIMAL', class: 'badge-success', level: 'optimal' };
    }
}
